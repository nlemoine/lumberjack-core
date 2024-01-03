<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use League\Route\Dispatcher as RouteDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Helpers;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;

class Dispatcher extends RouteDispatcher
{
    public function __construct(
        private Router $router
    ) {
    }

    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $message = '';
        try {
            $symfonyRequest = (new HttpFoundationFactory())->createRequest($request);
            $this->router->getContext()->fromRequest($symfonyRequest);
            $route = $this->router->matchRequest($symfonyRequest);
            $GLOBALS['wp_query']->is_404 = false;
            \do_action('app.route_matched', new CurrentRoute($route, $this->router->getGenerator()));
        } catch (MethodNotAllowedException $e) {
            $message = $e->getMessage();
            $allowed = $e->getAllowedMethods();
            $this->setMethodNotAllowedDecoratorMiddleware($allowed);
        } catch (NoConfigurationException $e) {
            $message = $e->getMessage();
            $this->setNotFoundDecoratorMiddleware();
        } catch (ResourceNotFoundException $e) {
            $message = $e->getMessage();
            $this->setNotFoundDecoratorMiddleware();
        }

        if (isset($route['_controller'])) {
            $vars = \array_filter($route, fn ($key) => !\str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
            $route = $this->ensureHandlerIsRoute($route['_controller'], $method, $uri)->setVars($vars);
            $this->setFoundMiddleware($route);
            $request = $this->requestWithRouteAttributes($request, $route);
        }

        return $this->handle($request);
    }
}
