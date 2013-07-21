<?php

namespace Dezull\Silex\Provider\DBALPaginatorServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\DBALQueryBuilderSubscriber;
use Knp\Bundle\PaginatorBundle\Subscriber\SlidingPaginationSubscriber;

/**
 * KnpPaginatorBundle's DBAL paginator integration for Silex.
 *
 * @author Dzul Nizam <dezull@gmail.com>
 */
class DBALPaginatorServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['dezull.dbal_paginator.subscriber.dbal_querybuilder']);

        $app['dispatcher']->addListener(
            KernelEvents::REQUEST,
            array($app['dezull.dbal_paginator.subscriber.pagination'], 'onKernelRequest'),
            0
        );
        $app['dispatcher']->addSubscriber($app['dezull.dbal_paginator.subscriber.pagination']);
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['dezull.dbal_paginator.template.pagination'] = null;
        $app['dezull.dbal_paginator.template.sortable'] = null;
        $app['dezull.dbal_paginator.pagination.page_range'] = 5;

        $app['dezull.dbal_paginator.paginator'] = $app->share(function() use ($app) {
            return new Paginator($app['dispatcher']);
        });

        $app['dezull.dbal_paginator.subscriber.pagination'] = $app->share(function() use ($app) {
            return new SlidingPaginationSubscriber(array(
                "defaultPaginationTemplate" => $app['dezull.dbal_paginator.template.pagination'],
                "defaultSortableTemplate" => $app['dezull.dbal_paginator.template.sortable'],
                "defaultFiltrationTemplate" => null, /* Not implemented */
                "defaultPageRange" => $app['dezull.dbal_paginator.pagination.page_range'],
            ));
        });

        $app['dezull.dbal_paginator.subscriber.dbal_querybuilder'] = $app->share(function() use ($app) {
            return new DBALQueryBuilderSubscriber;
        });

        $app['dezull.knp_paginator.twig.extension.paginator'] = $app->share(function() use ($app) {
            return new Twig\PaginationExtension($app['request'], $app['url_generator']);
        });

        $app['dezull.dbal_paginator'] = $app->share(function() use ($app) {
            // FIXME: Should not add Twig extension here, figure out how to inject Request
            $app['twig']->addExtension($app['dezull.knp_paginator.twig.extension.paginator']);

            return $app['dezull.dbal_paginator.paginator'];
        });
    }
}
