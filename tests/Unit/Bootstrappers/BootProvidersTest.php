<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\BootProviders;

class BootProvidersTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testBootsAllRegisteredProviders()
    {
        $app = new Application();

        $provider1 = Mockery::mock(new TestServiceProvider1());
        $provider1->shouldReceive('boot')->with($app)->once();
        $provider2 = Mockery::mock(new TestServiceProvider2());
        $provider2->shouldReceive('boot')->with($app)->once();

        $app->register($provider1);
        $app->register($provider2);

        $bootProvidersBootstrapper = new BootProviders();
        $bootProvidersBootstrapper->bootstrap($app);
    }

    // /** @test */
    // public function boots_all_registered_front_providers()
    // {
    //     $app = new Application();

    //     $provider1 = Mockery::mock(new TestServiceProvider1());
    //     $provider1->shouldReceive('boot')->with($app)->once();
    //     // $provider1->shouldReceive('bootFront')->with($app)->once();
    //     $provider2 = Mockery::mock(new TestServiceProvider2());
    //     $provider2->shouldReceive('boot')->with($app)->once();
    //     // $provider2->shouldReceive('bootFront')->with($app)->once();

    //     $app->register($provider1);
    //     $app->register($provider2);

    //     $bootProvidersBootstrapper = new BootProviders();
    //     $bootProvidersBootstrapper->bootstrap($app);
    // }

    // /** @test */
    // public function boots_all_registered_admin_providers()
    // {

    //     $app = new Application();

    //     $provider1 = Mockery::mock(new TestServiceProvider1());
    //     $provider1->shouldReceive('boot')->with($app)->once();
    //     // $provider1->shouldReceive('bootAdmin')->with($app)->once();
    //     $provider2 = Mockery::mock(new TestServiceProvider2());
    //     $provider2->shouldReceive('boot')->with($app)->once();
    //     // $provider2->shouldReceive('bootAdmin')->with($app)->once();

    //     $app->register($provider1);
    //     $app->register($provider2);

    //     $bootProvidersBootstrapper = new BootProviders();
    //     $bootProvidersBootstrapper->bootstrap($app);
    // }
}

class TestServiceProvider1
{
    public function boot(Application $app)
    {
    }
}

class TestServiceProvider2
{
    public function boot(Application $app)
    {
    }
}
