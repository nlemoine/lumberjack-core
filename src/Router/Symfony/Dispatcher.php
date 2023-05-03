<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use League\Route\Dispatcher as RouteDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

        if (isset($route)) {
            $route = $this->ensureHandlerIsRoute($route['_controller'], $method, $uri);
            $this->setFoundMiddleware($route);
            $request = $this->requestWithRouteAttributes($request, $route);
        }

        return $this->handle($request);
    }
}
