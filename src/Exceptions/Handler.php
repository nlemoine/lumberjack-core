<?php

namespace Rareloop\Lumberjack\Exceptions;

use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Contracts\ExceptionHandler;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Router\Responsable;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler implements ExceptionHandler
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function render(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        if ($exception instanceof Responsable) {
            return $exception->toResponse($request);
        }

        return $this->prepareResponse($request, $exception);
    }

    public function renderForConsole($output, Throwable $e)
    {
        (new ConsoleApplication())->renderThrowable($e, $output);
    }

    /**
     * Prepare a response for the given exception.
     *
     * @param  \Psr\Http\Request  $request
     */
    protected function prepareResponse($request, Throwable $exception): ResponseInterface
    {
        if (!$this->isHttpException($exception)) {
            // $exception = new HttpException(500, $exception->getMessage());
        }

        if ($this->app->get(Config::class)->get('app.debug')) {
            return $this->convertExceptionToResponse($exception);
        }

        try {
            return new TimberResponse(
                'errors/error.html.twig',
                [
                    'exception' => $exception,
                ],
                500
            );
        } catch (\Exception $customRenderException) {
            return $this->convertExceptionToResponse($exception);
        }
    }

    /**
     * Create a response for the given exception.
     */
    protected function convertExceptionToResponse(Throwable $exception): ResponseInterface
    {
        try {
            $projectPath = $this->app->get('paths.project');
        } catch (\Exception $e) {
            $projectPath = null;
        }

        $renderer = new HtmlErrorRenderer(
            $this->app->get(Config::class)->get('app.debug'),
            null,
            null,
            $projectPath
        );

        return new HtmlResponse(
            $renderer->render($exception)->getAsString(),
            500,
            []
        );
    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * @param Throwable $e Exception thrown.
     */
    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpException;
    }
}
