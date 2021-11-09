<?php

namespace Rareloop\Lumberjack\Providers;

use Brain\Hierarchy\Finder\CallbackTemplateFinder;
use Brain\Hierarchy\QueryTemplate;
use Laminas\Diactoros\ServerRequestFactory;
use mindplay\middleman\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\AbstractController;
use Rareloop\Router\Invoker;
use Rareloop\Router\ProvidesControllerMiddleware;
use Rareloop\Router\ResponseFactory;
use function Symfony\Component\String\u;
use Tightenco\Collect\Support\Collection;
use WP_Query;

class WordPressControllersServiceProvider extends ServiceProvider
{
    protected ?AbstractController $resolvedController = null;

    public function boot()
    {
        if (!\is_admin()) {
            \add_action('pre_get_posts', [$this, 'handleQuery'], PHP_INT_MAX);
        }
        \add_filter('template_redirect', [$this, 'handleWordPressController'], PHP_INT_MAX);
        \add_filter('lumberjack_controller_namespace', function ($namespace) {
            return 'App\\Http\\Controllers\\';
        }, 1);
    }

    public function handleQuery(WP_Query $query)
    {
        if (!$query->is_main_query()) {
            return;
        }

        $controller = $this->resolveController();
        if (!$controller) {
            return;
        }

        $controller->handleQuery($query);
    }

    /**
     * Handle WordPress controllers
     */
    public function handleWordPressController(): void
    {
        $controller = $this->resolveController();
        if (!$controller) {
            return;
        }

        $request = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        $response = $this->handleRequest($request, $controller, 'handle');
        $this->app->shutdown($response);
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

        return \class_exists($controllerFqns) ? $controllerFqns : '';
    }

    public function handleRequest(ServerRequestInterface $request, AbstractController $controller, string $methodName): ResponseInterface
    {
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

    protected function resolveController(bool $trigger_filters = true): ?AbstractController
    {
        // Don't handle those requests (robots.txt, HEAD, etc.)
        if (!QueryTemplate::mainQueryTemplateAllowed()) {
            return null;
        }

        if ($this->resolvedController !== null) {
            return $this->resolvedController;
        }

        $finder = new CallbackTemplateFinder([$this, 'getControllerClass']);

        $query_template = new QueryTemplate($finder);

        $controller_class = $query_template->findTemplate(null, $trigger_filters);

        if (!$controller_class) {
            return null;
        }

        $this->resolvedController = $this->app->get($controller_class);

        $this->app->requestHasBeenHandled();

        return $this->resolvedController;
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
