<?php

namespace Rareloop\Lumberjack\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\BootProviders;
use Rareloop\Lumberjack\Bootstrappers\LoadConfiguration;
use Rareloop\Lumberjack\Bootstrappers\RegisterAliases;
use Rareloop\Lumberjack\Bootstrappers\RegisterExceptionHandler;
use Rareloop\Lumberjack\Bootstrappers\RegisterFacades;
use Rareloop\Lumberjack\Bootstrappers\RegisterLogger;
use Rareloop\Lumberjack\Bootstrappers\RegisterProviders;
use Rareloop\Lumberjack\Bootstrappers\RegisterRequestHandler;
use Rareloop\Lumberjack\Http\Lumberjack;

class LumberjackTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testBootstrapShouldPassBootstrappersToApp()
    {
        $app = Mockery::mock(Application::class . '[bootstrapWith]');
        $app->shouldReceive('bootstrapWith')->with([
            LoadConfiguration::class,
            RegisterLogger::class,
            RegisterExceptionHandler::class,
            RegisterFacades::class,
            RegisterProviders::class,
            BootProviders::class,
            RegisterAliases::class,
            RegisterRequestHandler::class,
        ])->once();

        $kernal = new Lumberjack($app);
        $kernal->bootstrap();
    }
}
