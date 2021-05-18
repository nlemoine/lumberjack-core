<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use DI\NotFoundException;
use Exception;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Contracts\ExceptionHandler;
use Rareloop\Lumberjack\Facades\Config;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Throwable;
use function Http\Response\send;

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

        // if (Config::get('app.debug')) {
        //     $this->handler = Debug::enable();
        // } else {
        //     $this->handler = ErrorHandler::register();
        // }
        $this->handler = ErrorHandler::register();
        $this->handler->setExceptionHandler([$this, 'handleException']);
    }

    public function handleException(\Throwable $exception)
    {
        try {
            $this->getExceptionHandler()->report($exception);
        } catch (Exception $e) {
        }

        if ($this->app->runningInConsole()) {
            $this->renderForConsole($exception);
        } else {
            $this->renderHttpResponse($exception);
        }
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function renderForConsole(Throwable $e)
    {
        $this->getExceptionHandler()->renderForConsole(new ConsoleOutput(), $e);
    }

    /**
     * Render an exception as an HTTP response and send it.
     *
     * @param  \Throwable  $e
     * @return void
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

    public function send(ResponseInterface $response)
    {
        send($response);
    }
}
