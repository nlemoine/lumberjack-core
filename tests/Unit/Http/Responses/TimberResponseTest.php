<?php

namespace Rareloop\Lumberjack\Test\Http\Responses;

use Hamcrest\Arrays\IsArrayContainingKeyValuePair;
use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Http\Responses\TimberResponse;
use Rareloop\Lumberjack\ViewModel;
use Timber\Timber;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * The above is required as we're using alias mocks which persist between tests
 * https://laracasts.com/discuss/channels/testing/mocking-a-class-persists-over-tests/replies/103075
 */
class TimberResponseTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testConstructorCallsTimberCompile()
    {
        $context = [
            'foo' => 'bar',
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', IsArrayContainingKeyValuePair::hasKeyValuePair('foo', 'bar'))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);

        $this->assertSame(123, $response->getStatusCode());
        $this->assertSame('testing123', $response->getBody()->__toString());
    }

    public function testExceptionIsThrownIfTwigFileIsNotFound()
    {
        $this->expectException(\Rareloop\Lumberjack\Exceptions\TwigTemplateNotFoundException::class);
        $this->expectExceptionMessage('template.twig');

        $context = [
            'foo' => 'bar',
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', IsArrayContainingKeyValuePair::hasKeyValuePair('foo', 'bar'))
            ->once()
            ->andReturn(false);

        $response = new TimberResponse('template.twig', $context, 123);

        $this->assertSame(123, $response->getStatusCode());
        $this->assertSame('testing123', $response->getBody()->__toString());
    }

    public function testCanSetHeaders()
    {
        $headers = [
            'X-Test-Header' => 'testing',
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')->once()->andReturn('testing123');

        $response = new TimberResponse('template.twig', [], 200, $headers);

        $headers = $response->getHeaders();

        $this->assertNotNull($headers['X-Test-Header']);
        $this->assertSame('testing', $headers['X-Test-Header'][0]);
    }

    public function testDefaultStatusCodeIs200()
    {
        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')->once()->andReturn('testing123');

        $response = new TimberResponse('template.twig', []);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContextsWithViewModelsAreConverted()
    {
        $context = [
            'foo' => TestViewModel::createFromArray([
                'bar' => 123,
            ]),
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', Mockery::on(function ($passedContext) {
                $this->assertIsArray($passedContext['foo']);
                $this->assertSame(123, $passedContext['foo']['bar']);

                return true;
            }))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);
    }

    public function testContextsWithViewModelsAtLowerLevelsOfNestingAreConverted()
    {
        $context = [
            'foo' => [
                'bar' => TestViewModel::createFromArray([
                    'baz' => 123,
                ]),
            ],
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', Mockery::on(function ($passedContext) {
                $this->assertIsArray($passedContext['foo']);
                $this->assertIsArray($passedContext['foo']['bar']);
                $this->assertSame(123, $passedContext['foo']['bar']['baz']);

                return true;
            }))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);
    }

    public function testOriginalDataStructureIsNotMutated()
    {
        $context = [
            'foo' => TestViewModel::createFromArray([
                'bar' => 123,
            ]),
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->once()
            ->andReturn('testing123');

        new TimberResponse('template.twig', $context, 123);

        $this->assertInstanceOf(TestViewModel::class, $context['foo']);
    }

    public function testContextsWithCollectionsAreConverted()
    {
        $context = [
            'foo' => \collect([
                [
                    'bar' => 123,
                ],
            ]),

        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', Mockery::on(function ($passedContext) {
                $this->assertIsArray($passedContext['foo']);
                $this->assertSame(123, $passedContext['foo'][0]['bar']);

                return true;
            }))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);
    }

    public function testContextsWithCollectionsAtLowerLevelsOfNestingAreConverted()
    {
        $context = [
            'foo' => [
                'bar' => \collect([
                    [
                        'baz' => 123,
                    ],
                ]),

            ],
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', Mockery::on(function ($passedContext) {
                $this->assertIsArray($passedContext['foo']);
                $this->assertIsArray($passedContext['foo']['bar']);
                $this->assertSame(123, $passedContext['foo']['bar'][0]['baz']);

                return true;
            }))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);
    }

    public function testContextsWithViewModelsInCollectionsAreConverted()
    {
        $context = [
            'foo' => \collect([
                TestViewModel::createFromArray([
                    'bar' => 123,
                ]),
            ]),
        ];

        $timber = Mockery::mock('alias:' . Timber::class);
        $timber->shouldReceive('compile')
            ->with('template.twig', Mockery::on(function ($passedContext) {
                $this->assertIsArray($passedContext['foo']);
                $this->assertSame(123, $passedContext['foo'][0]['bar']);

                return true;
            }))
            ->once()
            ->andReturn('testing123');

        $response = new TimberResponse('template.twig', $context, 123);
    }
}

class TestViewModel extends ViewModel
{
    public $bar;

    public $baz;

    public static function createFromArray(array $array)
    {
        $vm = new static();

        foreach ($array as $key => $value) {
            $vm->{$key} = $value;
        }

        return $vm;
    }
}
