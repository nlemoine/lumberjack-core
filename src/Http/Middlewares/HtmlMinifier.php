<?php

namespace Rareloop\Lumberjack\Http\Middlewares;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use voku\helper\HtmlMin;

/**
 * Minifies HTML responses, including inline scripts, styles and style attributes.
 */
final class HtmlMinifier implements MiddlewareInterface
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(?StreamFactoryInterface $streamFactory = null)
    {
        $this->streamFactory = $streamFactory ?? Factory::getStreamFactory();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (\stripos($response->getHeaderLine('Content-Type'), 'text/html') !== 0) {
            return $response;
        }

        return $response
            ->withBody($this->streamFactory->createStream($this->minify((string) $response->getBody())))
            ->withoutHeader('Content-Length');
    }

    public function minify(string $html): string
    {
        $htmlMin = new HtmlMin();
        $htmlMin->attachObserverToTheDomLoop(new InlineAssetsMinifier());

        return \trim($htmlMin->minify($html));
    }
}
