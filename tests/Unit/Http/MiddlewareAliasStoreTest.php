<?php

namespace Rareloop\Lumberjack\Test\Http;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Rareloop\Lumberjack\Http\MiddlewareAliasStore;

class MiddlewareAliasStoreTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCanRegisterAnAliasForAMiddlewareObject()
    {
        $store = new MiddlewareAliasStore();
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $store->set('middlewarekey', $middleware);

        $this->assertSame($middleware, $store->get('middlewarekey'));
    }

    public function testCanRegisterAnAliasForAMiddlewareClosureFactory()
    {
        $store = new MiddlewareAliasStore();
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $store->set('middlewarekey', function () use ($middleware) {
            return $middleware;
        });

        $this->assertSame($middleware, $store->get('middlewarekey'));
    }

    public function testCanRegisterAnAliasForAClassname()
    {
        $store = new MiddlewareAliasStore();

        $store->set('middlewarekey', MASTestClass::class);

        $this->assertInstanceOf(MASTestClass::class, $store->get('middlewarekey'));
    }

    public function testCanRegisterAnAliasWithParamsForAMiddlewareClosureFactory()
    {
        $store = new MiddlewareAliasStore();
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $store->set('middlewarekey', function ($param1, $param2) use ($middleware) {
            $this->assertSame('123', $param1);
            $this->assertSame('abc', $param2);
            return $middleware;
        });

        $this->assertSame($middleware, $store->get('middlewarekey:123,abc'));
    }

    public function testCanRegisterAnAliasWithParamsForAClassname()
    {
        $store = new MiddlewareAliasStore();

        $store->set('middlewarekey', MASTestClassWithConstructorParams::class);
        $middleware = $store->get('middlewarekey:123,abc');

        $this->assertInstanceOf(MASTestClassWithConstructorParams::class, $middleware);
        $this->assertSame('123', $middleware->param1);
        $this->assertSame('abc', $middleware->param2);
    }

    public function testCanCheckIfAliasExists()
    {
        $store = new MiddlewareAliasStore();
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $this->assertFalse($store->has('middlewarekey'));

        $store->set('middlewarekey', $middleware);

        $this->assertTrue($store->has('middlewarekey'));
    }

    public function testCanCheckIfAliasExistsWhenStringContainsParams()
    {
        $store = new MiddlewareAliasStore();
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $this->assertFalse($store->has('middlewarekey'));

        $store->set('middlewarekey', $middleware);

        $this->assertTrue($store->has('middlewarekey:param1,param2'));
    }
}

class MASTestClass
{
}

class MASTestClassWithConstructorParams
{
    public $param1;

    public $param2;

    public function __construct($param1, $param2)
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}
