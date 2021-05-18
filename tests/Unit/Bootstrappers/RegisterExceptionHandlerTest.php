<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use Brain\Monkey\Functions;
use ErrorException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mockery;
use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\RegisterExceptionHandler;
use Rareloop\Lumberjack\Contracts\ExceptionHandler as ExceptionHandlerContract;
use Rareloop\Lumberjack\Exceptions\Handler;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Rareloop\Router\Responsable;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RegisterExceptionHandlerTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    /**
     * @test
     */
    public function errors_are_converted_to_exceptions()
    {
        $this->expectException(ErrorException::class);
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        trigger_error('Test error', E_USER_ERROR);
    }

    /**
     * @test
     */
    public function E_USER_NOTICE_errors_converted_to_exceptions()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $handler = Mockery::mock(ExceptionHandlerContract::class);
        $app->bind(ExceptionHandlerContract::class, $handler);

        $handler->shouldReceive('report')->once()->with(Mockery::on(function ($e) {
            return $e->getSeverity() === E_USER_NOTICE && $e->getMessage() === 'Test Error';
        }));
        $handler->shouldReceive('renderForConsole')->once();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        $bootstrapper->handleException(new ErrorException('Test Error', 0, E_USER_NOTICE));
    }

    /**
     * @test
     */
    public function E_USER_DEPRECATED_errors_are_converted_to_exceptions()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $handler = Mockery::mock(ExceptionHandlerContract::class);
        $app->bind(ExceptionHandlerContract::class, $handler);

        $handler->shouldReceive('report')->once()->with(Mockery::on(function ($e) {
            return $e->getSeverity() === E_USER_DEPRECATED && $e->getMessage() === 'Test Error';
        }));
        $handler->shouldReceive('renderForConsole')->once();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        $bootstrapper->handleException(new ErrorException('Test Error', 0, E_USER_DEPRECATED));
    }

    /** @test */
    public function handle_exception_should_call_handlers_report_and_render_methods()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $mock = $this->createPhpSapiNameMock('fpm-fcgi', 'Rareloop\Lumberjack');
        $mock->enable();

        $app = new Application();

        $exception = new \Exception('Test Exception');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $app->bind('request', $request);

        $handler = Mockery::mock(Handler::class);
        $handler->shouldReceive('report')->with($exception)->once();
        $handler->shouldReceive('render')->with($request, $exception)->once()->andReturn(new Response());
        $app->bind(ExceptionHandlerContract::class, $handler);

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        $bootstrapper->handleException($exception);
    }

    /** @test */
    // public function handle_exception_should_call_handlers_report_and_render_methods_using_an_error()
    // {
    //     Functions\expect('is_admin')->once()->andReturn(false);

    //     $mock = $this->createPhpSapiNameMock('fpm-fcgi', 'Rareloop\Lumberjack');
    //     $mock->enable();

    //     $app = new Application;

    //     $error = new \Error('Test Exception');
    //     $request = new ServerRequest([], [], '/test/123', 'GET');
    //     $app->bind('request', $request);

    //     $handler = Mockery::mock(Handler::class);
    //     $handler->shouldReceive('report')->with(Mockery::type(\ErrorException::class))->once();
    //     $handler->shouldReceive('render')->with($request, Mockery::type(\ErrorException::class))->once()->andReturn(new Response());
    //     $app->bind(ExceptionHandlerContract::class, $handler);

    //     $bootstrapper = Mockery::mock(RegisterExceptionHandler::class.'[send]');
    //     $bootstrapper->shouldReceive('send')->once();
    //     $bootstrapper->bootstrap($app);

    //     $bootstrapper->handleException($error);
    // }

    /** @test */
    public function handle_exception_should_call_handlers_report_and_render_methods_even_if_request_is_not_set_in_the_container()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $mock = $this->createPhpSapiNameMock('fpm-fcgi', 'Rareloop\Lumberjack');
        $mock->enable();

        $app = new Application();

        $exception = new \Exception('Test Exception');

        $handler = Mockery::mock(Handler::class);
        $handler->shouldReceive('report')->with($exception)->once();
        $handler->shouldReceive('render')->with(Mockery::type(ServerRequest::class), $exception)->once()->andReturn(new Response());
        $app->bind(ExceptionHandlerContract::class, $handler);

        $bootstrapper = Mockery::mock(RegisterExceptionHandler::class . '[send]');
        $bootstrapper->shouldReceive('send')->once();
        $bootstrapper->bootstrap($app);

        $bootstrapper->handleException($exception);
    }

    private function createPhpSapiNameMock($value, $namespace)
    {
        $builder = new MockBuilder();

        $builder->setNamespace($namespace)
                ->setName('php_sapi_name')
                ->setFunction(
                    function () use ($value) {
                        return $value;
                    }
                );

        return $builder->build();
    }
}

class ResponsableException extends \Exception implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
