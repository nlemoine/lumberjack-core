<?php

namespace Rareloop\Lumberjack\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ExceptionHandler
{
    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e);

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @param  \Throwable  $e
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Throwable
     */
    public function render(ServerRequestInterface $request, Throwable $e);

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Throwable  $e
     * @return void
     */
    public function renderForConsole($output, Throwable $e);
}
