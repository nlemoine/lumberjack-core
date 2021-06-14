<?php

namespace Rareloop\Lumberjack\Test\Providers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Contracts\MiddlewareAliases;
use Rareloop\Lumberjack\Http\Lumberjack;
use Rareloop\Lumberjack\Http\Router;
use Rareloop\Lumberjack\Providers\RouterServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Rareloop\Router\MiddlewareResolver;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class RouterServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testRouterObjectIsConfigured()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        $app->register(new RouterServiceProvider($app));
        $lumberjack->bootstrap();

        $this->assertTrue($app->has('router'));
        $this->assertSame($app->get('router'), $app->get(Router::class));
    }

    public function testMiddlewareAliasObjectsAreConfigured()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        $app->register(new RouterServiceProvider($app));
        $lumberjack->bootstrap();

        $this->assertTrue($app->has('middleware-alias-store'));
        $this->assertSame($app->get('middleware-alias-store'), $app->get(MiddlewareAliases::class));

        $this->assertTrue($app->has('middleware-resolver'));
        $this->assertSame($app->get('middleware-resolver'), $app->get(MiddlewareResolver::class));
    }

    public function testConfiguredRouterCanResolveMiddlewareAliases()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/');
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        $app->register(new RouterServiceProvider($app));
        $lumberjack->bootstrap();

        $router = $app->get(Router::class);
        $store = $app->get(MiddlewareAliases::class);
        $store->set('middleware-key', new RSPAddHeaderMiddleware('X-Key', 'abc'));
        $request = new ServerRequest([], [], '/test/123', 'GET');

        $router->get('/test/123', function () {
        })->middleware('middleware-key');
        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Key'));
        $this->assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    public function testBasedirIsSetFromWordpressConfig()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $request = new ServerRequest([], [], '/sub-path/test/123', 'GET');

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        $app->register(new RouterServiceProvider($app));
        $lumberjack->bootstrap();

        $router = $app->get('router');
        $router->get('/test/123', function () {
            return 'abc123';
        });

        $response = $router->match($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->__toString());
    }

    public function testWpLoadedActionIsBound()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $this->assertSame(10, \has_action('wp_loaded', 'function ()'));
    }

    public function testRequestObjectIsBoundIntoTheContainer()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $provider->processRequest($request);

        $this->assertSame($request, $app->get('request'));
    }

    public function testUnmatchedRequestWillNotCallAppShutdownMethod()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $response = new TextResponse('Testing 123', 404);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(0)->with($response);

        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $router = Mockery::mock(Router::class . '[match]', $app);
        $router->shouldReceive('match')->andReturn($response)->once();

        $app->bind('router', $router);

        $provider->processRequest(new ServerRequest([], [], '/test/123', 'GET'));
    }

    public function testMatchedRequestWillCallAppShutdownMethod()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $response = new TextResponse('Testing 123', 200);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(1)->with($response);

        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $router = Mockery::mock(Router::class . '[match]', $app);
        $router->shouldReceive('match')->andReturn($response)->once();

        $app->bind('router', $router);

        $provider->processRequest(new ServerRequest([], [], '/test/123', 'GET'));
    }

    public function testLumberjackRouterResponseFilterIsFiredWhenRequestIsProcessed()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');

        $request = new ServerRequest([], [], '/test/123', 'GET');
        $response = new HtmlResponse('testing 123');

        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown')->times(1)->with($response);
        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $router = Mockery::mock(Router::class . '[match]', $app);
        $router->shouldReceive('match')->andReturn($response)->once();

        $app->bind('router', $router);

        Filters\expectApplied('lumberjack_router_response')
            ->once()
            ->with($response, $request);

        $provider->processRequest($request);
    }

    public function testMatchedRequestWillMarkRequestHandledInApp()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $response = new TextResponse('Testing 123', 200);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown');

        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $router = Mockery::mock(Router::class . '[match]', $app);
        $router->shouldReceive('match')->andReturn($response)->once();

        $app->bind('router', $router);

        $provider->processRequest(new ServerRequest([], [], '/test/123', 'GET'));

        $this->assertTrue($app->hasRequestBeenHandled());
    }

    public function testUnmatchedRequestWillNotMarkRequestHandledInApp()
    {
        Functions\when('is_admin')->justReturn(false);

        $this->setSiteUrl('http://example.com/sub-path/');
        $response = new TextResponse('Testing 123', 404);
        $app = Mockery::mock(Application::class . '[shutdown]', [__DIR__ . '/..']);
        $app->shouldReceive('shutdown');

        $lumberjack = new Lumberjack($app);
        $provider = new RouterServiceProvider($app);

        $app->register($provider);
        $lumberjack->bootstrap();

        $router = Mockery::mock(Router::class . '[match]', $app);
        $router->shouldReceive('match')->andReturn($response)->once();

        $app->bind('router', $router);

        $provider->processRequest(new ServerRequest([], [], '/test/123', 'GET'));

        $this->assertFalse($app->hasRequestBeenHandled());
    }

    private function setSiteUrl($url)
    {
        Functions\when('get_bloginfo')->alias(function ($key) use ($url) {
            if ($key === 'url') {
                return $url;
            }
        });
    }
}

class RSPAddHeaderMiddleware implements MiddlewareInterface
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
