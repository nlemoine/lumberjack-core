<?php

namespace Rareloop\Lumberjack\Test\Exceptions;

use Blast\Facades\FacadeFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Exceptions\Handler;

class HandlerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testRenderShouldReturnAnHtmlResponseWhenDebugIsEnabled()
    {
        $app = new Application();
        FacadeFactory::setContainer($app);
        $config = new Config();
        $config->set('app.debug', true);
        $app->bind(Config::class, $config);

        $exception = new \Exception('Test Exception');
        $handler = new Handler($app);

        $response = $handler->render(new ServerRequest(), $exception);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    // public function test_render_should_return_an_html_response_when_debug_is_disabled()
    // {
    //     $app = new Application();
    //     FacadeFactory::setContainer($app);
    //     $config = new Config();
    //     $config->set('app.debug', false);
    //     $app->bind(Config::class, $config);

    //     $exception = new \Exception('Test Exception');
    //     $handler = new Handler($app);

    //     $response = $handler->render(new ServerRequest(), $exception);

    //     $this->assertInstanceOf(HtmlResponse::class, $response);
    // }

    public function testRenderShouldIncludeStackTraceWhenDebugIsEnabled()
    {
        $app = new Application();
        FacadeFactory::setContainer($app);
        $config = new Config();
        $config->set('app.debug', true);
        $app->bind(Config::class, $config);

        $exception = new \Exception('Test Exception');
        $handler = new Handler($app);

        $response = $handler->render(new ServerRequest(), $exception);
        $this->assertStringContainsString('Test Exception', $response->getBody()->getContents());
    }

    /** @test */
    // public function test_render_should_not_include_stack_trace_when_debug_is_disabled()
    // {
    //     $app = new Application();
    //     FacadeFactory::setContainer($app);
    //     $config = new Config();
    //     $config->set('app.debug', false);
    //     $app->bind(Config::class, $config);

    //     $exception = new \Exception('Test Exception');
    //     $handler = new Handler($app);

    //     $response = $handler->render(new ServerRequest(), $exception);

    //     $this->assertStringNotContainsString('Test Exception', $response->getBody()->getContents());
    // }
}
