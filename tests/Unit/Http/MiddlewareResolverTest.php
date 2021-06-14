<?php

namespace Rareloop\Lumberjack\Test\Http;

use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Contracts\MiddlewareAliases;
use Rareloop\Lumberjack\Http\MiddlewareAliasStore;
use Rareloop\Lumberjack\Http\MiddlewareResolver;

class MiddlewareResolverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCanResolveAKeyFromTheContainer()
    {
        $app = new Application();
        $resolver = new MiddlewareResolver($app, new MiddlewareAliasStore());

        $datetime = new \DateTime();
        $app->bind('datetime', $datetime);

        $this->assertSame($datetime, $resolver->resolve('datetime'));
    }

    public function testCanResolveAnObjectFromAClassnameFromTheContainer()
    {
        $app = new Application();
        $resolver = new MiddlewareResolver($app, new MiddlewareAliasStore());

        $this->assertInstanceOf(MRTestClass::class, $resolver->resolve(MRTestClass::class));
    }

    public function testCanResolveAMiddlewareAlias()
    {
        $app = new Application();
        $store = Mockery::mock(MiddlewareAliases::class);
        $store->shouldReceive('has')->with('middlewarekey')->once()->andReturn(true);
        $store->shouldReceive('get')->with('middlewarekey')->once()->andReturn(new MRTestClass());
        $resolver = new MiddlewareResolver($app, $store);

        $this->assertInstanceOf(MRTestClass::class, $resolver->resolve('middlewarekey'));
    }

    public function testNonStringValuesAreReturnedAsIs()
    {
        $app = new Application();
        $resolver = new MiddlewareResolver($app, new MiddlewareAliasStore());

        $datetime = new \DateTime();

        $this->assertSame($datetime, $resolver->resolve($datetime));
    }
}

class MRTestClass
{
}
