<?php

namespace Rareloop\Lumberjack\Providers;

use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Http\Exception\MethodNotAllowedException as LeagueMethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\ApplicationStrategy;
use PLL_Base;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\ServerRequest;
use Rareloop\Lumberjack\Router\Symfony\Loader\ArrayLoader;
use Rareloop\Lumberjack\Router\Symfony\Router;
use Rareloop\Lumberjack\Router\Symfony\Matcher\RedirectableCompiledUrlMatcher;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
                'matcher_class' => RedirectableCompiledUrlMatcher::class,
                // 'strict_requirements' => false, // TODO: remove when this is solid
            ];
        });
        $this->app->singleton('router.prefixes', function () {
            if (!$this->app->has('polylang')) {
                return null;
            }
            $pll = $this->app->get('polylang');
            if (!$pll instanceof PLL_Base) {
                return null;
            }
            $prefixes = [];
            $hideDefault = $pll->options['hide_default'] ?? false;
            $defaultLang = $pll->options['default_lang'] ?? null;
            $prefix = $this->app->get('polylang.url_prefix');
            $languages_prefixes = \array_column($this->app->get('polylang.languages'), $prefix);
            $prefixes = \array_combine($languages_prefixes, \array_map(function ($l) use ($hideDefault, $defaultLang) {
                if ($hideDefault && $l === $defaultLang) {
                    return '';
                }
                return '/' . $l;
            }, $languages_prefixes));
            return $prefixes;
        });
        $this->app->singleton('router.loader', function () {
            return new ArrayLoader($this->app->get('router.prefixes'));
        });
        $this->app->singleton('router.core', function () {
            $current_locale = $this->app->get('locale.short');
            if ($this->app->has('polylang')) {
                $current_language = $this->app->get('polylang.current_language');
                $prefix = $this->app->get('polylang.url_prefix');
                $current_locale = $current_language->{$prefix} ?? $current_locale;
            }
            return new SymfonyRouter(
                $this->app->get('router.loader'),
                $this->app->get('router.routes', []),
                $this->app->get('router.options'),
                null,
                null,
                $current_locale
            );
        });
        $this->app->singleton('router.generator', function () {
            return $this->app->get('router.core')->getGenerator();
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
        \add_action('wp', [$this, 'processRequest'], 1000); // Load after inpsyde/assets
        \add_filter('timber/twig', [$this, 'addTwigExtension']);
        \add_filter('document_title_parts', [$this, 'setTitle'], 100);
    }

    public function setTitle(array $parts)
    {
        try {
            $route = $this->app->get('current_route');
            if (!isset($route['_title'])) {
                return $parts;
            }
        } catch(\Exception $e) {
            return $parts;
        }

        $parts['title'] = $route['_title'];

        return $parts;
    }

    public function processRequest()
    {
        // Don't process request if it has matched a WordPress route
        // Will avoid to run the router logic on every request
        if (!\is_404()) {
            return;
        }
        $request = ServerRequest::fromRequest(ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        ));
        $this->doProcessRequest($request);
    }

    public function addTwigExtension(Environment $twig): Environment
    {
        $twig->addExtension(new RoutingExtension($this->get('router.generator')));
        return $twig;
    }

    public function doProcessRequest(ServerRequestInterface $request)
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
