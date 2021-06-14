<?php

namespace Rareloop\Lumberjack\Test\Http\Responses;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;
use Rareloop\Lumberjack\Session\SessionManager;

class RedirectResponseTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testIsAPsrResponseImplementation()
    {
        $response = new RedirectResponse('/another.php');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHasA302StatusCodeByDefault()
    {
        $response = new RedirectResponse('/another.php');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testCanSpecifyA301StatusCode()
    {
        $response = new RedirectResponse('/another.php', 301);

        $this->assertSame(301, $response->getStatusCode());
    }

    public function testSetsTheLocationHeader()
    {
        $response = new RedirectResponse('/another.php');

        $this->assertSame('/another.php', $response->getHeader('Location')[0]);
    }

    public function testCanCallWithMethodToFlashDataToTheSession()
    {
        $app = new Application();
        $session = Mockery::mock(SessionManager::class);
        $session->shouldReceive('flash')->with('key', 'value')->once();
        $session->shouldReceive('flash')->with('foo', 'bar')->once();
        $app->bind('session', $session);

        $response = new RedirectResponse('/another.php');
        // Make sure we get an instance of RedirectResponse back from 'with'
        $this->assertSame($response, $response->with('key', 'value')->with('foo', 'bar'));
    }

    public function testCanCallWithMethodToFlashDataToTheSessionUsingAnArray()
    {
        $app = new Application();
        $session = Mockery::mock(SessionManager::class);
        $session->shouldReceive('flash')->with([
            'key' => 'value',
            'foo' => 'bar',
        ])->once();
        $app->bind('session', $session);

        $response = new RedirectResponse('/another.php');
        $response->with([
            'key' => 'value',
            'foo' => 'bar',
        ]);
    }
}
