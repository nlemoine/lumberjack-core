<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use League\Route\Router as BaseRouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Router as SymfonyRouter;

class Router extends BaseRouter
{
    public function __construct(
        private SymfonyRouter $router
    ) {
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = (new Dispatcher($this->router))->setStrategy($this->getStrategy());

        foreach ($this->getMiddlewareStack() as $middleware) {
            if (\is_string($middleware)) {
                $dispatcher->lazyMiddleware($middleware);
                continue;
            }

            $dispatcher->middleware($middleware);
        }

        return $dispatcher->dispatchRequest($request);
    }

    public function setRouter($router)
    {
        $this->router = $router;
    }
}
