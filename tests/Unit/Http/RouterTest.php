<?php

namespace Rareloop\Lumberjack\Test\Http;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Http\Router;

/**
 * Ensure all class_alias calls are reset each time
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RouterTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testControllerHasNamespaceAdded()
    {
        \class_alias(RouterTestController::class, 'App\Http\Controllers\MyController');
        $router = new Router();

        $route = $router->get('/test/123', 'MyController@test');

        $this->assertSame('App\Http\Controllers\MyController@test', $route->getActionName());
    }

    public function testControllerDoesNotHaveNamespaceAddedWhenItAlreadyExists()
    {
        $router = new Router();

        $route = $router->get('/test/123', RouterTestController::class . '@test');

        $this->assertSame(RouterTestController::class . '@test', $route->getActionName());
    }

    public function testControllerDoesNotHaveNamespaceAddedWhenItIsCallable()
    {
        $router = new Router();
        $controller = new RouterTestController();

        $route = $router->get('/test/123', [$controller, 'test']);

        $this->assertSame(RouterTestController::class . '@test', $route->getActionName());
    }

    public function testControllerDoesNotHaveNamespaceAddedWhenItIsClosure()
    {
        $router = new Router();
        $controller = new RouterTestController();

        $route = $router->get('/test/123', function () {
        });

        $this->assertSame('Closure', $route->getActionName());
    }

    public function testCanExtendPostBehaviourWithMacros()
    {
        Router::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Router();

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', Router::testFunctionAddedByMacro());
    }

    public function testCanExtendPostBehaviourWithMixin()
    {
        Router::mixin(new RouterMixin());

        $queryBuilder = new Router();

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class RouterMixin
{
    public function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}

class RouterTestController
{
    public function test()
    {
    }
}
