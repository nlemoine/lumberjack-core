<?php

namespace Rareloop\Lumberjack\Http;

use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\BootProviders;
use Rareloop\Lumberjack\Bootstrappers\LoadConfiguration;
use Rareloop\Lumberjack\Bootstrappers\RegisterExceptionHandler;
use Rareloop\Lumberjack\Bootstrappers\RegisterFacades;
use Rareloop\Lumberjack\Bootstrappers\RegisterLocale;
use Rareloop\Lumberjack\Bootstrappers\RegisterLogger;
use Rareloop\Lumberjack\Bootstrappers\RegisterPath;
use Rareloop\Lumberjack\Bootstrappers\RegisterProviders;
use Rareloop\Lumberjack\Bootstrappers\RegisterRequestHandler;

class Lumberjack
{
    protected $bootstrappers = [
        LoadConfiguration::class,
        RegisterLogger::class,
        RegisterExceptionHandler::class,
        RegisterFacades::class,
        RegisterPath::class,
        RegisterLocale::class,
        RegisterProviders::class,
        BootProviders::class,
        RegisterRequestHandler::class,
    ];

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function bootstrap()
    {
        $this->app->bootstrapWith($this->bootstrappers());
    }

    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }
}
