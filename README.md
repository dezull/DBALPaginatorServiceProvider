# DBAL Paginator Service Provider for Silex

Based on [KnpLabs' PaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle).

## Usage

1) When registering `TwigServiceProvider`, add the paginator's template path (create your own or just use KnpPaginatorBundle's).

```php
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        /* Your other templates */
        __DIR__.'/../vendor/knplabs/knp-paginator-bundle/Knp/Bundle/PaginatorBundle/Resources/views',
    ),
));
```

2) Register `DBALPaginatorServiceProvider`.

```php
$app->register(new Dezull\Silex\Provider\DBALPaginatorServiceProvider\DBALPaginatorServiceProvider(), array(
    /* The following assumes you use the template path as in step #1 */
    'dezull.dbal_paginator.template.pagination' => 'Pagination/twitter_bootstrap_pagination.html.twig',
    'dezull.dbal_paginator.template.sortable' => 'Pagination/sortable_link.html.twig',
));
```

3) In your controller

```php
public function indexAction(Request $request, Application $app)
{
    $page = (int) $request->query->get('page', 1);
    $sortKey = $request->query->get('sort', 's.id');
    $direction = $request->query->get('direction', 'desc');

    /* Doctrine DBAL QueryBuilder */
    $qb = $app['db']->createQueryBuilder()
        ->select('s.*')
        ->from('sometable', 's')
        ->orderBy($sortKey, $direction);

    $pagination = $app['dezull.dbal_paginator']->paginate(
        $qb,
        $page,
        20 /* per page limit */
    );

    return $app['twig']->render('Foo/index.html.twig', array(
        'pagination' => $pagination,
    ));
}
```

4) To render the pagination in the template

```twig
{{ dezull_dbal_pagination_render(pagination) }}
```
## TODO

1) Remove dependency on KnpPaginatorBundle
