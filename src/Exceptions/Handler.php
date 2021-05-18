<?php

namespace Rareloop\Lumberjack\Exceptions;

use Exception;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Contracts\ExceptionHandler;
use Rareloop\Lumberjack\Facades\Config;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Router\Responsable;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use function Http\Response\send;

class Handler implements ExceptionHandler
{
    protected $app;

    protected $dontReport = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function report(Throwable $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $logger = $this->app->get('logger');
        $logger->error($e);
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

    public function shouldReport(Throwable $e)
    {
        return !in_array(get_class($e), $this->dontReport);
    }


    /**
     * Prepare a response for the given exception.
     *
     * @param  \Psr\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function prepareResponse($request, Throwable $exception): ResponseInterface
    {
        if (!$this->isHttpException($exception)) {
            $exception = new HttpException(500, $exception->getMessage());
        }

        if (Config::get('app.debug')) {
            return $this->convertExceptionToResponse($exception);
        }

        try {
            return new TimberResponse(
                'errors/error.html.twig',
                ['exception' => $exception],
                $exception->getStatusCode()
            );
        } catch (\Exception $customRenderException) {
            return $this->convertExceptionToResponse($exception);
        }
    }

    /**
     * Create a response for the given exception.
     *
     * @param  \Throwable  $exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function convertExceptionToResponse(Throwable $exception): ResponseInterface
    {
        try {
            $projectPath = $this->app->get('paths.project');
        } catch (\Exception $e) {
            $projectPath = null;
        }

        $renderer = new HtmlErrorRenderer(
            Config::get('app.debug'),
            null,
            null,
            $projectPath
        );
        return new HtmlResponse(
            $renderer->render($exception)->getAsString(),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * @param Throwable $e Exception thrown.
     * @return bool
     */
    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpException;
    }
}
