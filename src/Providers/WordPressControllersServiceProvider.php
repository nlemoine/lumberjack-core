<?php

namespace Rareloop\Lumberjack\Providers;

use Brain\Hierarchy\Hierarchy;
use Brain\Hierarchy\QueryTemplate;
use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Middleware\MiddlewareAwareInterface;
use Middleland\Dispatcher;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Hierarchy\Finder\ControllerClassFinder;
use Rareloop\Lumberjack\Http\AbstractController;
use Rareloop\Lumberjack\Http\ServerRequest;
use WP_Query;

class WordPressControllersServiceProvider extends ServiceProvider
{
    protected ?AbstractController $resolvedController = null;

    protected ?array $resolvedHierarchy = null;

    protected int $resolutionCount = 0;

    protected Hierarchy $hierarchy;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->hierarchy = new Hierarchy();
    }

    public function register()
    {
        $this->app->singleton(ControllerClassFinder::class, function () {
            return new ControllerClassFinder($this->getConfig('app.controller_namespaces', ['App\\Http\\Controllers\\']));
        });
    }

    public function boot()
    {
        if (\is_admin()) {
            return;
        }

        \add_action('pre_get_posts', [$this, 'handleQuery'], 20);
        \add_filter('template_redirect', [$this, 'handleWordPressController'], PHP_INT_MAX);
    }

    public function handleQuery(WP_Query $query)
    {
        if (!$query->is_main_query()) {
            return;
        }

        $controller = null;
        try {
            $controller = $this->resolveController();
        } catch (\Throwable $e) {
        }

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

    public function handleRequest(RequestInterface $request, AbstractController $controller, string $methodName): ResponseInterface
    {
        $middlewares = [];

        if ($controller instanceof MiddlewareAwareInterface) {
            $middlewares = $controller->getMiddlewareStack();
        }

        // $middlewares[] = function ($request) use ($controller, $methodName) {
        //     $invoker = new Invoker($this->app);
        //     $output = $invoker->setRequest($request)->call([$controller, $methodName]);
        //     return ResponseFactory::create($request, $output);
        // };

        // Middleware to handle request
        $middlewares[] = function ($request) use ($controller, $methodName) {
            return $controller->{$methodName}(ServerRequest::fromRequest($request));
        };

        $dispatcher = new Dispatcher($middlewares, $this->app);

        $response = $dispatcher->handle($request);

        return $response;
    }

    protected function resolveController(bool $trigger_filters = true): ?AbstractController
    {
        // Don't handle those requests (robots.txt, HEAD, etc.)
        if (!QueryTemplate::mainQueryTemplateAllowed()) {
            return null;
        }

        $this->resolutionCount = $this->resolutionCount + 1;

        // Check if hierarchy has changed, resolve it again
        if ($this->resolutionCount > 1 && $this->hierarchy->hierarchy() === $this->resolvedHierarchy) {
            return $this->resolvedController;
        }

        $finder = $this->app->get(ControllerClassFinder::class);
        $query_template = new QueryTemplate($finder);

        $controller_class = $query_template->findTemplate(null, $trigger_filters);
        if (!$controller_class) {
            return null;
        }

        $this->resolvedHierarchy = $this->hierarchy->hierarchy();
        $this->resolvedController = $this->app->get($controller_class);

        $this->app->requestHasBeenHandled();

        return $this->resolvedController;
    }
}
