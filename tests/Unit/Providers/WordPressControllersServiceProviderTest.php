<?php

namespace Rareloop\Lumberjack\Test\Providers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Contracts\MiddlewareAliases;
use Rareloop\Lumberjack\Http\Controller;
use Rareloop\Lumberjack\Providers\RouterServiceProvider;
use Rareloop\Lumberjack\Providers\WordPressControllersServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Rareloop\Router\Responsable;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class WordPressControllersServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testTemplateIncludeFilterIsAppliedOnBoot()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new WordPressControllersServiceProvider($app);

        $app->register($provider);
        $app->boot();

        $this->assertSame(PHP_INT_MAX, \has_filter('template_include', [$provider, 'handleTemplateInclude']));
    }

    public function testHandleTemplateIncludeMethodIncludesTheRequestedFile()
    {
        $app = new Application(__DIR__ . '/../');

        $this->assertNotContains(__DIR__ . '/includes/single.php', \get_included_files());

        $provider = new WordPressControllersServiceProvider($app);
        $provider->handleTemplateInclude(__DIR__ . '/includes/single.php');

        $this->assertContains(__DIR__ . '/includes/single.php', \get_included_files());
    }

    public function testHandleTemplateIncludeMethodSetsDetailsInContainerWhenControllerIsNotPresent()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->handleTemplateInclude(__DIR__ . '/includes/single.php');

        $this->assertTrue($app->has('__wp-controller-miss-template'));
        $this->assertTrue($app->has('__wp-controller-miss-controller'));
        $this->assertSame('single.php', $app->get('__wp-controller-miss-template'));
        $this->assertSame('App\SingleController', $app->get('__wp-controller-miss-controller'));
    }

    public function testHandleTemplateIncludeMethodDoesNotSetDetailsInContainerWhenControllerIsPresent()
    {
        $response = new TextResponse('Testing 123', 200);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(1);

        $provider = Mockery::mock(WordPressControllersServiceProvider::class . '[handleRequest]', [$app]);
        $provider->shouldReceive('handleRequest')->once()->andReturn($response);
        $provider->boot($app);

        $provider->handleTemplateInclude(__DIR__ . '/includes/single.php');

        $this->assertFalse($app->has('__wp-controller-miss-template'));
        $this->assertFalse($app->has('__wp-controller-miss-controller'));
    }

    public function testCanGetNameOfControllerFromTemplate()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new WordPressControllersServiceProvider($app);

        $mappings = [
            'App\\SingleController'         => __DIR__ . '/includes/single.php',
            'App\\SingleEventsController'   => __DIR__ . '/includes/single_events.php',
            'App\\SingleRlEventsController' => __DIR__ . '/includes/single_rl_events.php',
        ];

        foreach ($mappings as $className => $template) {
            $this->assertSame($className, $provider->getControllerClassFromTemplate($template));
        }
    }

    public function testCanGetSpecialCaseNameOf404ControllerFromTemplate()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new WordPressControllersServiceProvider($app);

        $this->assertSame('App\\Error404Controller', $provider->getControllerClassFromTemplate(__DIR__ . 'includes/404.php'));
    }

    public function testHandleTemplateIncludeAppliesFiltersOnControllerNameAndNamespace()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new WordPressControllersServiceProvider($app);

        Filters\expectApplied('lumberjack_controller_name')
            ->once()
            ->with('SingleController');

        Filters\expectApplied('lumberjack_controller_namespace')
            ->once()
            ->with('App\\');

        $provider->getControllerClassFromTemplate(__DIR__ . 'includes/single.php');
    }

    public function testHandleRequestReturnsFalseIfControllerDoesNotExist()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new WordPressControllersServiceProvider($app);

        $response = $provider->handleRequest(new ServerRequest(), 'Does\\Not\\Exist', 'handle');

        $this->assertFalse($response);
    }

    public function testHandleRequestWritesWarningToLogsIfControllerDoesNotExist()
    {
        $log = Mockery::mock(Logger::class);
        $log->shouldReceive('warning')->once()->with('Controller class `Does\Not\Exist` not found');

        $app = new Application(__DIR__ . '/../');
        $app->bind('logger', $log);
        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot();

        $response = $provider->handleRequest(new ServerRequest(), 'Does\\Not\\Exist', 'handle');
    }

    public function testHandleRequestWillMarkRequestHandledInAppIfControllerDoesExist()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot();

        $response = $provider->handleRequest(new ServerRequest(), TestController::class, 'handle');

        $this->assertTrue($app->hasRequestBeenHandled());
    }

    public function testHandleRequestWillNotMarkRequestHandledInAppIfControllerDoesNotExist()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot();

        $response = $provider->handleRequest(new ServerRequest(), 'Does\\Not\\Exist', 'handle');

        $this->assertFalse($app->hasRequestBeenHandled());
    }

    public function testHandleRequestReturnsResponseWhenControllerDoesExist()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestController::class, 'handle');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleRequestReturnsResponseWhenControllerReturnsAResponsable()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerReturningAResponsable::class, 'handle');

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame('testing123', $response->getBody()->getContents());
    }

    public function testHandleRequestResolvesConstructorParamsFromContainer()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithConstructorParams::class, 'handle');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleRequestResolvesControllerMethodParamsFromContainer()
    {
        $app = new Application(__DIR__ . '/../');

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithHandleParams::class, 'handle');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleRequestSupportsMiddleware()
    {
        $app = new Application(__DIR__ . '/../');
        $controller = new TestControllerWithMiddleware($app);
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));
        $app->bind(TestControllerWithMiddleware::class, $controller);

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithMiddleware::class, 'handle');

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    public function testHandleRequestSupportsMiddlewareAppliedToASpecificMethodUsingOnly()
    {
        $app = new Application(__DIR__ . '/../');
        $controller = new TestControllerWithMiddleware($app);
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only('notHandle');
        $app->bind(TestControllerWithMiddleware::class, $controller);

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithMiddleware::class, 'handle');

        $this->assertFalse($response->hasHeader('X-Header'));
    }

    public function testHandleRequestSupportsMiddlewareAppliedToASpecificMethodUsingExcept()
    {
        $app = new Application(__DIR__ . '/../');
        $controller = new TestControllerWithMiddleware($app);
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except('handle');
        $app->bind(TestControllerWithMiddleware::class, $controller);

        $provider = new WordPressControllersServiceProvider($app);
        $provider->boot($app);

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithMiddleware::class, 'handle');

        $this->assertFalse($response->hasHeader('X-Header'));
    }

    public function testHandleRequestSupportsMiddlewareAliases()
    {
        Functions\when('get_bloginfo')->alias(function ($key) {
            if ($key === 'url') {
                return 'http://example.com';
            }
        });

        $app = new Application(__DIR__ . '/../');

        $controller = new TestControllerWithMiddleware($app);
        $controller->middleware('middleware-key');
        $app->bind(TestControllerWithMiddleware::class, $controller);

        $routerProvider = new RouterServiceProvider($app);
        $provider = new WordPressControllersServiceProvider($app);
        $routerProvider->register();
        $routerProvider->boot();
        $provider->boot($app);

        $store = $app->get(MiddlewareAliases::class);
        $store->set('middleware-key', new AddHeaderMiddleware('X-Header', 'testing123'));

        $response = $provider->handleRequest(new ServerRequest(), TestControllerWithMiddleware::class, 'handle');

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    public function testHandleTemplateIncludeWillCallAppShutdownWhenItHasHandledARequest()
    {
        $response = new TextResponse('Testing 123', 404);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(1)->with($response);

        $provider = Mockery::mock(WordPressControllersServiceProvider::class . '[handleRequest]', [$app]);
        $provider->shouldReceive('handleRequest')->once()->andReturn($response);
        $provider->boot($app);

        $provider->handleTemplateInclude(__DIR__ . '/includes/single.php');
    }

    public function testHandleTemplateIncludeWillNotCallAppShutdownWhenItHasNotHandledARequest()
    {
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(0);

        $provider = Mockery::mock(WordPressControllersServiceProvider::class . '[handleRequest]', [$app]);
        $provider->shouldReceive('handleRequest')->once()->andReturn(false);
        $provider->boot($app);

        $provider->handleTemplateInclude(__DIR__ . '/includes/single.php');
    }
}

class TestController
{
    public function handle()
    {
    }
}

class TestControllerWithConstructorParams
{
    public function __construct(Application $app)
    {
    }

    public function handle()
    {
    }
}

class TestControllerWithHandleParams
{
    public function handle(Application $app)
    {
    }
}

class MyResponsable implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}

class TestControllerReturningAResponsable
{
    public function handle()
    {
        return new MyResponsable();
    }
}

class TestControllerWithMiddleware extends Controller
{
    public function handle()
    {
    }
}

class AddHeaderMiddleware implements MiddlewareInterface
{
    private $key;

    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader($this->key, $this->value);
    }
}
