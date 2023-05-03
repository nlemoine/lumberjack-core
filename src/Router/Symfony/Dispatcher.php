<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use League\Route\Dispatcher as RouteDispatcher;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Dispatcher extends RouteDispatcher
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        try {
            $symfonyRequest = (new HttpFoundationFactory())->createRequest($request);
            $this->router->getContext()->fromRequest($symfonyRequest);
            $route = $this->router->matchRequest($symfonyRequest);
        } catch (MethodNotAllowedException $e) {
            $allowed = $e->getAllowedMethods();
            $this->setMethodNotAllowedDecoratorMiddleware($allowed);
        } catch (NoConfigurationException $e) {
            $this->setNotFoundDecoratorMiddleware();
        } catch (ResourceNotFoundException $e) {
            $this->setNotFoundDecoratorMiddleware();
        }

        $route = $this->ensureHandlerIsRoute($route['_controller'], $method, $uri);
        $this->setFoundMiddleware($route);
        $request = $this->requestWithRouteAttributes($request, $route);

        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }

    protected function ensureHandlerIsRoute($matchingHandler, $httpMethod, $uri): Route
    {
        if ($matchingHandler instanceof Route) {
            return $matchingHandler;
        }

        return new Route($httpMethod, $uri, $matchingHandler);
    }
}
