<?php

namespace Rareloop\Lumberjack\Http\Middlewares;

use Gajus\Dindent\Indenter;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IndentMiddleware implements MiddlewareInterface
{
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory = null
    ) {
        $this->streamFactory = $streamFactory ?: Factory::getStreamFactory();
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        if (\stripos($response->getHeaderLine('Content-Type'), 'text/html') !== 0) {
            return $response;
        }

        $indenter = new Indenter();
        $stream = $this->streamFactory->createStream($indenter->indent((string) $response->getBody()));

        return $response->withBody($stream)
            ->withoutHeader('Content-Length');
    }
}
