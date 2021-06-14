<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use DI\NotFoundException;
use Exception;
use function Http\Response\send;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Contracts\ExceptionHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Throwable;

class RegisterExceptionHandler
{
    private $app;

    private $handler;

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        if (\is_admin()) {
            return;
        }

        $config = $this->app->get(Config::class);

        if ($config->get('app.debug')) {
            $this->handler = Debug::enable();
        } else {
            $this->handler = ErrorHandler::register();
        }

        try {
            // Log silenced errors
            $this->handler->screamAt(\E_ALL);
            $this->handler->setDefaultLogger($this->app->get(LoggerInterface::class));
        } catch (\Throwable $e) {
        }

        $this->handler->setExceptionHandler([$this, 'handleException']);
    }

    public function handleException(\Throwable $exception)
    {
        if ($this->app->runningInConsole()) {
            $this->renderForConsole($exception);
        } else {
            $this->renderHttpResponse($exception);
        }
    }

    public function send(ResponseInterface $response)
    {
        @send($response);
    }

    /**
     * Render an exception to the console.
     */
    protected function renderForConsole(Throwable $e)
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput(), $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     */
    protected function renderHttpResponse(Throwable $e)
    {
        try {
            $request = $this->app->get('request');
        } catch (NotFoundException $notFoundException) {
            $request = ServerRequestFactory::fromGlobals();
        }

        $this->send($this->getExceptionHandler()->render($request, $e));
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return \Rareloop\Lumberjack\Contracts\ExceptionHandler
     */
    protected function getExceptionHandler()
    {
        return $this->app->get(ExceptionHandler::class);
    }
}
