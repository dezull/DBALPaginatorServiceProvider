<?php

namespace Dezull\Silex\Provider\DBALPaginatorServiceProvider\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Based on Knp\Bundle\PaginatorBundle\Twig\Extension\PaginationExtension.
 *
 * @author Dzul Nizam <dezull@gmail.com>
 */
class PaginationExtension extends \Twig_Extension
{
    /**
     * @var \Twig_Environment
     */
    protected $environment;

    protected $request;

    protected $urlGenerator;

    public function __construct(Request $request, UrlGenerator $urlGenerator)
    {
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            'dezull_dbal_pagination_render' => new \Twig_Function_Method($this, 'render', array('is_safe' => array('html'))),
            'dezull_dbal_pagination_sortable' => new \Twig_Function_Method($this, 'sortable', array('is_safe' => array('html'))),
        );
    }

    /**
     * Renders the pagination template
     *
     * @param string $template
     * @param array $queryParams
     * @param array $viewParams
     *
     * @return string
     */
    public function render($pagination, $template = null, array $queryParams = array(), array $viewParams = array())
    {
        $route = $this->request->attributes->get('_route');
        $params = array_merge($this->request->query->all(), $this->request->attributes->all());

        $data = $pagination->getPaginationData();
        $data['route'] = $pagination->getRoute();
        $data['query'] = $pagination->getParams();
        $merged = array_merge(
            $pagination->getPaginatorOptions(), // options given to paginator when paginated
            $pagination->getCustomParameters(), // all custom parameters for view
            $data // merging base route parameters last, to avoid broke of integrity
        );

        return $this->environment->render(
            $template ?: $pagination->getTemplate(),
            $merged
        );
    }

    /**
     * Create a sort url for the field named $title
     * and identified by $key which consists of
     * alias and field. $options holds all link
     * parameters like "alt, class" and so on.
     *
     * $key example: "article.title"
     *
     * @param string $title
     * @param string $key
     * @param array $options
     * @param array $params
     * @param string $template
     * @return string
     */
    public function sortable($pagination, $title, $key, $options = array(), $params = array(), $template = null)
    {
        $options = array_merge(array(
            'absolute' => false,
            'translationParameters' => array(),
            'translationDomain' => null,
            'translationCount' => null,
        ), $options);

        $params = array_merge($pagination->getParams(), $params);

        $direction = isset($options[$pagination->getPaginatorOption('sortDirectionParameterName')])
            ? $options[$pagination->getPaginatorOption('sortDirectionParameterName')]
            : (isset($options['defaultDirection']) ? $options['defaultDirection'] : 'asc')
        ;

        $sorted = $pagination->isSorted($key, $params);

        if ($sorted) {
            $direction = $params[$pagination->getPaginatorOption('sortDirectionParameterName')];
            $direction = (strtolower($direction) == 'asc') ? 'desc' : 'asc';
            $class = $direction == 'asc' ? 'desc' : 'asc';

            if (isset($options['class'])) {
                $options['class'] .= ' ' . $class;
            } else {
                $options['class'] = $class;
            }
        } else {
            $options['class'] = 'sortable';
        }

        if (is_array($title) && array_key_exists($direction, $title)) {
            $title = $title[$direction];
        }

        $params = array_merge(
            $params,
            array(
                $pagination->getPaginatorOption('sortFieldParameterName') => $key,
                $pagination->getPaginatorOption('sortDirectionParameterName') => $direction,
                $pagination->getPaginatorOption('pageParameterName') => 1 // reset to 1 on sort
            )
        );

        $options['href'] = $this->urlGenerator->generate($pagination->getRoute(), $params, $options['absolute']);

        if (null !== $options['translationDomain']) {
            if (null !== $options['translationCount']) {
                $title = $this->translator->transChoice($title, $options['translationCount'], $options['translationParameters'], $options['translationDomain']);
            } else {
                $title = $this->translator->trans($title, $options['translationParameters'], $options['translationDomain']);
            }
        }

        if (!isset($options['title'])) {
            $options['title'] = $title;
        }

        $template = $template ?: $pagination->getSortableTemplate();

        unset($options['absolute'], $options['translationDomain'], $options['translationParameters']);

        return $this->environment->render($template, array_merge(
            $pagination->getPaginatorOptions(),
            $pagination->getCustomParameters(),
            compact('options', 'title', 'direction', 'sorted', 'key')
        ));
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return 'dezull.dbal_paginator';
    }
}

