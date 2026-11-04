<?php

namespace Rareloop\Lumberjack\Test\Http;

use Brain\Monkey\Functions;
use Env\Env;
use Middlewares\BasicAuthentication;
use Middlewares\Minifier;
use Mockery;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rareloop\Lumberjack\Http\Controller;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

/**
 * One process per test: WP_ENV and WP_ENVIRONMENT_TYPE are constants.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AbstractControllerTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();

        foreach (['WP_ENV', 'WP_ENVIRONMENT_TYPE', 'DISABLE_HTML_MIN', 'BASIC_AUTH_USER', 'BASIC_AUTH_PASSWORD'] as $name) {
            \putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
    }

    public function testOnlyWpEnvConstantIsUsedWhenWpEnvironmentTypeIsSetNowhere()
    {
        // Core falls back to production when WP_ENVIRONMENT_TYPE is set nowhere.
        Functions\when('wp_get_environment_type')->justReturn('production');
        \define('WP_ENV', 'development');

        $this->assertNotContains(Minifier::class, $this->middlewareClasses());
    }

    public function testOnlyWpEnvVariableIsUsedWhenWpEnvironmentTypeIsSetNowhere()
    {
        Functions\when('wp_get_environment_type')->justReturn('production');
        \putenv('WP_ENV=development');

        $this->assertNotContains(Minifier::class, $this->middlewareClasses());
    }

    public function testWpEnvironmentTypeConstantWinsOverWpEnv()
    {
        Functions\when('wp_get_environment_type')->justReturn('staging');
        \define('WP_ENVIRONMENT_TYPE', 'staging');
        \define('WP_ENV', 'preprod');

        $this->assertContains(Minifier::class, $this->middlewareClasses());
    }

    public function testWpEnvironmentTypeVariableWinsOverWpEnv()
    {
        Functions\when('wp_get_environment_type')->justReturn('development');
        \putenv('WP_ENVIRONMENT_TYPE=development');
        \define('WP_ENV', 'production');

        $this->assertNotContains(Minifier::class, $this->middlewareClasses());
    }

    public function testNothingSetMeansProduction()
    {
        Functions\when('wp_get_environment_type')->justReturn('production');

        $this->assertContains(Minifier::class, $this->middlewareClasses());
    }

    /**
     * @dataProvider disableHtmlMinProvider
     */
    public function testDisableHtmlMinIsReadThroughEnv(string $value, bool $minified)
    {
        Functions\when('wp_get_environment_type')->justReturn('production');
        // A project whose .env loader never calls putenv() (WP Starter) reads $_SERVER.
        Env::$options = (Env::$options & ~Env::USE_ENV_ARRAY) | Env::USE_SERVER_ARRAY;
        $_SERVER['DISABLE_HTML_MIN'] = $value;

        $this->assertSame($minified, \in_array(Minifier::class, $this->middlewareClasses(), true));
    }

    public function disableHtmlMinProvider(): array
    {
        return [
            'true'  => ['true', false],
            '1'     => ['1', false],
            'false' => ['false', true],
            '0'     => ['0', true],
        ];
    }

    public function testNoBasicAuthenticationWithoutCredentials()
    {
        Functions\when('wp_get_environment_type')->justReturn('development');
        \putenv('BASIC_AUTH_USER=admin');

        $this->assertNotContains(BasicAuthentication::class, $this->middlewareClasses());
    }

    public function testBasicAuthenticationAcceptsANumericPassword()
    {
        Functions\when('wp_get_environment_type')->justReturn('development');
        \putenv('BASIC_AUTH_USER=admin');
        \putenv('BASIC_AUTH_PASSWORD=1234');

        $controller = new Controller(Mockery::mock(ContainerInterface::class));
        $stack = [...$controller->getMiddlewareStack()];
        $this->assertCount(1, $stack);
        $this->assertInstanceOf(BasicAuthentication::class, $stack[0]);

        $handler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(204);
            }
        };
        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Basic ' . \base64_encode('admin:1234'));
        $this->assertSame(204, $stack[0]->process($request, $handler)->getStatusCode());

        $request = (new ServerRequest('GET', '/'))->withHeader('Authorization', 'Basic ' . \base64_encode('admin:wrong'));
        $this->assertSame(401, $stack[0]->process($request, $handler)->getStatusCode());
    }

    private function middlewareClasses(): array
    {
        $controller = new Controller(Mockery::mock(ContainerInterface::class));

        return \array_map('get_class', [...$controller->getMiddlewareStack()]);
    }
}
