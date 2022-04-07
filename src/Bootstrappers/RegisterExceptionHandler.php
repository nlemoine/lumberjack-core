<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use DI\NotFoundException;
use Exception;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
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

    private $thrownErrors = 0x1FFF; // E_ALL - E_DEPRECATED - E_USER_DEPRECATED

    private $scopedErrors = 0x1FFF; // E_ALL - E_DEPRECATED - E_USER_DEPRECATED

    private $tracedErrors = 0x77FB; // E_ALL - E_STRICT - E_PARSE

    private $screamedErrors = 0x55; // E_ERROR + E_CORE_ERROR + E_COMPILE_ERROR + E_PARSE

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        if (\is_admin()) {
            return;
        }

        $config = $this->app->get(Config::class);
        $debug = $config->get('app.debug');

        if ($debug) {
            $this->handler = Debug::enable();
        } else {
            $this->handler = ErrorHandler::register();
        }

        $this->tracedErrors = $config->get('app.error.trace_at') ?? $this->tracedErrors;
        $this->screamedErrors = $config->get('app.error.scream_at') ?? $this->screamedErrors;
        $this->thrownErrors = $config->get('app.error.throw_at') ?? $this->thrownErrors;

        try {
            // Log silenced errors
            $this->handler->traceAt($this->tracedErrors, true);
            $this->handler->screamAt($this->screamedErrors, true);
            $this->handler->throwAt($this->thrownErrors, true);
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
        @(new SapiEmitter())->emit($response);
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
