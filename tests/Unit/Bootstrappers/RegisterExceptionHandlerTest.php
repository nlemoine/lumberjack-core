<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use Brain\Monkey\Functions;
use ErrorException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mockery;
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

    public function testErrorsAreConvertedToExceptions()
    {
        $this->expectException(ErrorException::class);
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        \trigger_error('Test error', E_USER_ERROR);
    }

    public function testUserNoticeErrorsConvertedToExceptions()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $handler = Mockery::mock(ExceptionHandlerContract::class);
        $app->bind(ExceptionHandlerContract::class, $handler);

        $handler->shouldReceive('renderForConsole')->once();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        $bootstrapper->handleException(new ErrorException('Test Error', 0, E_USER_NOTICE));
    }

    public function testUserDeprecatedErrorsAreConvertedToExceptions()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $handler = Mockery::mock(ExceptionHandlerContract::class);
        $app->bind(ExceptionHandlerContract::class, $handler);

        $handler->shouldReceive('renderForConsole')->once();

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($app);
        $bootstrapper->handleException(new ErrorException('Test Error', 0, E_USER_DEPRECATED));
    }

    public function testHandleExceptionShouldCallHandlersRenderMethods()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $appMock = Mockery::mock($app);
        $appMock->shouldReceive('runningInConsole')->andReturn(false);

        $exception = new \Exception('Test Exception');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $appMock->bind('request', $request);

        $handler = Mockery::mock(Handler::class);
        $handler->shouldReceive('render')->with($request, $exception)->once()->andReturn(new Response());
        $appMock->bind(ExceptionHandlerContract::class, $handler);

        $bootstrapper = new RegisterExceptionHandler();
        $bootstrapper->bootstrap($appMock);
        $bootstrapper->handleException($exception);
    }

    public function testHandleExceptionShouldCallHandlersRenderMethodsEvenIfRequestIsNotSetInTheContainer()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application();
        $appMock = Mockery::mock($app);
        $appMock->shouldReceive('runningInConsole')->andReturn(false);

        $exception = new \Exception('Test Exception');

        $handler = Mockery::mock(Handler::class);
        $handler->shouldReceive('render')->with(Mockery::type(ServerRequest::class), $exception)->once()->andReturn(new Response());
        $appMock->bind(ExceptionHandlerContract::class, $handler);

        $bootstrapper = Mockery::mock(RegisterExceptionHandler::class . '[send]');
        $bootstrapper->shouldReceive('send')->once();
        $bootstrapper->bootstrap($appMock);

        $bootstrapper->handleException($exception);
    }
}

class ResponsableException extends \Exception implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
