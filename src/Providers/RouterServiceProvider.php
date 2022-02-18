<?php

namespace Rareloop\Lumberjack\Providers;

use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\ServerRequest;
use Rareloop\Lumberjack\Router\MiddlewareControllerAwareStrategy;
use League\Route\Strategy\ApplicationStrategy;

class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('router', function () {
            $strategy = new ApplicationStrategy();
            $strategy->setContainer($this->app);
            $router = new Router();
            $router->setStrategy($strategy);
            return $router;
        });

        // $this->app->bind('router.generator', function () use ($router) {
        //     $locale = $this->app->get('locale.short');
        //     $base_url = $this->app->get('url.home');
        //     $router::macro('generateUrl', function ($name, $arguments = [], $relative = false) use ($locale, $base_url) {
        //         $route_name = $name . '_' . $locale;
        //         if (!$this->has($route_name)) {
        //             $route_name = $name;
        //         }

        //         $path = $this->url($route_name, $arguments = []);

        //         return ($relative ? '' : \rtrim($base_url, '/')) . $path;
        //     });

        //     return $router;
        // });
    }

    public function boot()
    {
        \add_action('wp_loaded', function () {
            $request = ServerRequest::fromRequest(ServerRequestFactory::fromGlobals(
                $_SERVER,
                $_GET,
                $_POST,
                $_COOKIE,
                $_FILES
            ));

            $this->processRequest($request);
        }, 1000); // Load after inpsyde/assets
    }

    public function processRequest(ServerRequestInterface $request)
    {
        $this->app->bind('request', $request);
        try {
            $response = $this->app->get('router')->dispatch($request);
        } catch (\Exception $e) {
            return;
        }

        $response = \apply_filters('lumberjack_router_response', $response, $request);

        if ($response->getStatusCode() === 404) {
            return;
        }

        $this->app->requestHasBeenHandled();

        $this->app->shutdown($response);
    }

    private function getBasePathFromWPConfig()
    {
        // Infer the base path from the site's URL
        $siteUrl = \get_bloginfo('url');
        $siteUrlParts = \explode('/', \rtrim($siteUrl, ' //'));
        $siteUrlParts = \array_slice($siteUrlParts, 3);
        $basePath = \implode('/', $siteUrlParts);

        if (!$basePath) {
            $basePath = '/';
        } else {
            $basePath = '/' . $basePath . '/';
        }

        return $basePath;
    }
}
