<?php

use App\Controller\TestController;
use App\Service\GreetingService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require dirname(__DIR__).'/vendor/autoload.php';

$cacheDir = dirname(__DIR__).'/var/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$routes = new RouteCollection();
$routes->add('home', new Route('/', ['_controller' => TestController::class.'::index'], methods: ['GET']));
$routes->add('hello', new Route('/hello/{name}', ['_controller' => TestController::class.'::hello'], requirements: ['name' => '[A-Za-z0-9_-]+'], methods: ['GET']));
$routes->add('submit', new Route('/submit', ['_controller' => TestController::class.'::submit'], methods: ['POST']));
file_put_contents($cacheDir.'/routes.php', (new CompiledUrlMatcherDumper($routes))->dump());

$container = new ContainerBuilder();
$container->setDefinition('request_stack', new Definition(RequestStack::class));
$container->setDefinition('request_context', (new Definition(RequestContext::class))->setPublic(true));
$container->setDefinition('url_matcher', (new Definition(CompiledUrlMatcher::class))->setSynthetic(true)->setPublic(true));
$container->setDefinition('router_listener', new Definition(RouterListener::class, [
    new Reference('url_matcher'),
    new Reference('request_stack'),
    new Reference('request_context'),
]));
$container->setDefinition('event_dispatcher', (new Definition(EventDispatcher::class))
    ->addMethodCall('addSubscriber', [new Reference('router_listener')]));
$container->setDefinition(GreetingService::class, new Definition(GreetingService::class));
$container->setDefinition(TestController::class, new Definition(TestController::class, [new Reference(GreetingService::class)]))->setPublic(true);
$container->setDefinition('controller_resolver', new Definition(ContainerControllerResolver::class, [new Reference('service_container')]));
$container->setDefinition('argument_resolver', new Definition(ArgumentResolver::class));
$container->setDefinition('http_kernel', new Definition(HttpKernel::class, [
    new Reference('event_dispatcher'),
    new Reference('controller_resolver'),
    new Reference('request_stack'),
    new Reference('argument_resolver'),
]))->setPublic(true);
$container->compile();

$dumper = new PhpDumper($container);
file_put_contents($cacheDir.'/AppContainer.php', $dumper->dump(['class' => 'AppContainer']));
