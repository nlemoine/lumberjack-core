<?php

namespace Rareloop\Lumberjack\Test\Facades;

use Blast\Facades\FacadeFactory;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Facades\Router as RouterFacade;
use Rareloop\Lumberjack\Http\Router;

class RouterTest extends TestCase
{
    public function testFacade()
    {
        $app = new Application();
        FacadeFactory::setContainer($app);

        $router = new Router();
        $app->bind('router', $router);

        $this->assertInstanceOf(Router::class, RouterFacade::__instance());
        $this->assertSame($router, RouterFacade::__instance());
    }
}
