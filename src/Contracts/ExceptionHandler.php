<?php

namespace Rareloop\Lumberjack\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Throwable
     */
    public function render(ServerRequestInterface $request, Throwable $e);

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     */
    public function renderForConsole($output, Throwable $e);
}
