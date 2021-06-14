<?php

namespace Rareloop\Lumberjack\Test\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Rareloop\Lumberjack\Http\ServerRequest;
use Rareloop\Psr7ServerRequestExtension\InteractsWithInput;
use Rareloop\Psr7ServerRequestExtension\InteractsWithUri;
use Zend\Diactoros\ServerRequest as DiactorosServerRequest;

class ServerRequestTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testRequestIsPrs7Compliant()
    {
        $request = new ServerRequest();

        $this->assertInstanceOf(ServerRequestInterface::class, $request);
    }

    public function testRequestUsesExtensionTraits()
    {
        $request = new ServerRequest();

        $traits = \array_keys(\class_uses($request));

        $this->assertContains(InteractsWithInput::class, $traits);
        $this->assertContains(InteractsWithUri::class, $traits);
    }

    public function testCanCreateFromARequestInstance()
    {
        $request = new DiactorosServerRequest([], [], '/test/123', 'GET');

        $lumberjackRequest = ServerRequest::fromRequest($request);

        $this->assertInstanceOf(ServerRequest::class, $lumberjackRequest);
    }

    public function testFromRequestParsesJsonRequests()
    {
        $request = new DiactorosServerRequest([], [], '/test/123', 'POST', 'data://text/plain,{"foo": "bar"}', [
            'Content-Type' => 'application/json',
        ]);

        $lumberjackRequest = ServerRequest::fromRequest($request);

        $this->assertSame('bar', $lumberjackRequest->input('foo'));
    }

    public function testAjaxMethodReturnsTrueWhenFromAjax()
    {
        $request = new DiactorosServerRequest([], [], '/test/123', 'GET');
        $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');

        $lumberjackRequest = ServerRequest::fromRequest($request);

        $this->assertTrue($lumberjackRequest->ajax());
    }

    public function testAjaxMethodReturnsFalseWhenNotFromAjax()
    {
        $request = new DiactorosServerRequest([], [], '/test/123', 'GET');

        $lumberjackRequest = ServerRequest::fromRequest($request);

        $this->assertFalse($lumberjackRequest->ajax());
    }

    public function testGetMethodIsAlwaysUppercase()
    {
        $request1 = ServerRequest::fromRequest(new DiactorosServerRequest([], [], '/test/123', 'GET'));
        $request2 = ServerRequest::fromRequest(new DiactorosServerRequest([], [], '/test/123', 'get'));

        $this->assertSame('GET', $request1->getMethod());
        $this->assertSame('GET', $request2->getMethod());
    }
}
