<?php

namespace Rareloop\Lumberjack\Providers;

use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Http\Exception\MethodNotAllowedException as LeagueMethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\ServerRequest;
use Rareloop\Lumberjack\Router\Symfony\Loader\ArrayLoader;
use Rareloop\Lumberjack\Router\Symfony\Router;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Router as SymfonyRouter;
use Twig\Environment;

class SymfonyRouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('router.routes', function () {
            return $this->getConfig('routes', []);
        });
        $this->app->singleton('router.options', function () {
            $debug = $this->getConfig('app.debug');
            return [
                'debug'     => $this->getConfig('app.debug'),
                'cache_dir' => $debug ? null : $this->app->get('path.cache') . '/routes',
            ];
        });
        $this->app->singleton('router.loader', function () {
            return new ArrayLoader();
        });
        $this->app->singleton('router.core', function () {
            $context = new RequestContext();
            $context->setParameter('_locale', $this->app->get('locale'));
            return new SymfonyRouter(
                $this->app->get('router.loader'),
                $this->getConfig('routes', []),
                $this->app->get('router.options'),
                $context
            );
        });
        $this->app->singleton('router', function () {
            $strategy = new ApplicationStrategy();
            $strategy->setContainer($this->app);
            $router = new Router($this->app->get('router.core'));
            $router->setStrategy($strategy);
            return $router;
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

    public function addTwigExtension(Environment $twig): Environment
    {
        $twig->addExtension(new RoutingExtension($this->get('router.core')));
        return $twig;
    }

    public function processRequest(ServerRequestInterface $request)
    {
        $this->app->bind('request', $request);

        $router = $this->app->get('router');
        try {
            $response = $router->dispatch($request);
        } catch (MethodNotAllowedException $e) {
            return;
        } catch (LeagueMethodNotAllowedException $e) {
            return;
        } catch (ResourceNotFoundException $e) {
            return;
        } catch (NotFoundException $e) {
            return;
        } catch (NoConfigurationException $e) {
            return;
        }

        $response = \apply_filters('lumberjack_router_response', $response, $request);

        if ($response->getStatusCode() === 404) {
            return;
        }

        $this->app->requestHasBeenHandled();

        $this->app->shutdown($response);
    }
}
