<?php

namespace Rareloop\Lumberjack\Providers;

use Laminas\Diactoros\ServerRequestFactory;
use mindplay\middleman\Dispatcher;
use Psr\Http\Message\RequestInterface;
use Rareloop\Router\Invoker;
use Rareloop\Router\ProvidesControllerMiddleware;
use Rareloop\Router\ResponseFactory;
use Tightenco\Collect\Support\Collection;
use Brain\Hierarchy\Finder\CallbackTemplateFinder;
use Brain\Hierarchy\QueryTemplate;
use function Symfony\Component\String\u;

class WordPressControllersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('template_redirect', [$this, 'handleWordPressController'], PHP_INT_MAX);
        \add_filter('lumberjack_controller_namespace', function ($namespace) {
            return 'App\\Http\\Controllers\\';
        }, 1);
    }

    /**
     * Handle WordPress controllers
     */
    public function handleWordPressController() {

        // Don't handle those requests (robots.txt, HEAD, etc.)
        if(!QueryTemplate::mainQueryTemplateAllowed()) {
            return;
        }

        $finder = new CallbackTemplateFinder([$this, 'getControllerClass']);

        $queryTemplate = new QueryTemplate($finder);

        $controller = $queryTemplate->findTemplate();

        $request = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        $response = $this->handleRequest($request, $controller, 'handle');

        if ($response) {
            $this->app->shutdown($response);
        } else {
            $this->app->bind('__wp-controller-miss-template', $controller);
            $this->app->bind('__wp-controller-miss-controller', $controller);
        }
    }

    public function getControllerClass(string $template): string
    {
        if ($template === '404') {
            $template = 'error-404';
        }

        $template = $template . '-controller';

        $controllerClass = u($template)->camel()->title();

        $controllerName = \apply_filters('lumberjack_controller_name', $controllerClass);
        $controllerNamespace = \apply_filters('lumberjack_controller_namespace', 'App\\');

        $controllerFqns = $controllerNamespace . $controllerName;

        return class_exists($controllerFqns) ? $controllerFqns : '';
    }

    public function handleRequest(RequestInterface $request, $controllerName, $methodName)
    {
        $this->app->requestHasBeenHandled();

        $controller = $this->app->get($controllerName);

        $middlewares = [];

        if ($controller instanceof ProvidesControllerMiddleware) {
            $controllerMiddleware = new Collection($controller->getControllerMiddleware());

            $middlewares = $controllerMiddleware->reject(function ($cm) use ($methodName) {
                return $cm->excludedForMethod($methodName);
            })->map(function ($cm) {
                return $cm->middleware();
            })->all();
        }

        $middlewares[] = function ($request) use ($controller, $methodName) {
            $invoker = new Invoker($this->app);
            $output = $invoker->setRequest($request)->call([$controller, $methodName]);
            return ResponseFactory::create($request, $output);
        };

        $dispatcher = $this->createDispatcher($middlewares);
        return $dispatcher->handle($request);
    }

    private function createDispatcher(array $middlewares): Dispatcher
    {
        $resolver = null;

        if ($this->app->has('middleware-resolver')) {
            $resolver = function ($name) {
                return $this->app->get('middleware-resolver')->resolve($name);
            };
        }

        return new Dispatcher($middlewares, $resolver);
    }
}
