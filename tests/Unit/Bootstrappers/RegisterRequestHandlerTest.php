<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\RegisterRequestHandler;
use Rareloop\Lumberjack\Config;

class RegisterRequestHandlerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCallsFunctionOnAppWhenInDebugMode()
    {
        Functions\when('is_admin')->justReturn(false);

        $app = Mockery::mock(Application::class . '[detectWhenRequestHasNotBeenHandled]');
        $app->shouldReceive('detectWhenRequestHasNotBeenHandled')->once();

        $config = new Config();
        $config->set('app.debug', true);
        $app->bind('config', $config);

        $bootstrapper = new RegisterRequestHandler();
        $bootstrapper->bootstrap($app);
    }

    public function testDoesNotCallFunctionOnAppWhenNotInDebugMode()
    {
        $app = Mockery::mock(Application::class . '[detectWhenRequestHasNotBeenHandled]');
        $app->shouldNotReceive('detectWhenRequestHasNotBeenHandled');

        $config = new Config();
        $config->set('app.debug', false);
        $app->bind('config', $config);

        $bootstrapper = new RegisterRequestHandler();
        $bootstrapper->bootstrap($app);
    }
}
