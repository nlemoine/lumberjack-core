<?php

namespace Rareloop\Lumberjack\Providers;

use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\ServerRequest;
use Rareloop\Lumberjack\Router\Router;
use Rareloop\Lumberjack\Twig\Extensions\RoutingExtension;
use Twig\Environment;

class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Router::class, function () {
            $strategy = new ApplicationStrategy();
            $strategy->setContainer($this->app);
            $router = new Router();
            $router->setStrategy($strategy);
            return $router;
        });
        $this->app->singleton('router', function () {
            return $this->app->get(Router::class);
        });
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
        \add_filter('timber/twig', [$this, 'addTwigExtension']);
    }

    /**
     * Add the form extension to the Twig environment
     */
    public function addTwigExtension(Environment $twig): Environment
    {
        $twig->addExtension(new RoutingExtension($this->get('router')));
        return $twig;
    }

    public function processRequest(ServerRequestInterface $request)
    {
        $this->app->bind('request', $request);
        try {
            $response = $this->app->get('router')->dispatch($request);
        } catch (NotFoundException $e) {
            return;
        } catch (MethodNotAllowedException $e) {
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
