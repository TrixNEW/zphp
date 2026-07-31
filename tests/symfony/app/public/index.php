<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\RequestContext;

require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/var/cache/AppContainer.php';

$container = new AppContainer();
$context = $container->get('request_context');
$container->set('url_matcher', new CompiledUrlMatcher(
    require dirname(__DIR__).'/var/cache/routes.php',
    $context,
));
$kernel = $container->get('http_kernel');
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
