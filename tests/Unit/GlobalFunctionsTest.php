<?php

namespace Rareloop\Lumberjack\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Helpers;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GlobalFunctionsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        include_once(__DIR__ . '/../../src/functions.php');

        parent::setUp();
    }

    /**
     * @dataProvider globalHelperFunctions
     */
    public function testGlobalFunctionsAreRegistered($function)
    {
        $this->assertTrue(\function_exists($function));
    }

    /**
     * @dataProvider globalHelperFunctions
     */
    public function testGlobalFunctionsProxyCallsToStaticFunctions($function)
    {
        $helpers = Mockery::mock('alias:' . Helpers::class);
        $helpers->shouldReceive($function)->withArgs(['param1', 'param2'])->once();

        $function('param1', 'param2');
    }

    public static function globalHelperFunctions()
    {
        $reflection = new \ReflectionClass(Helpers::class);

        return \collect($reflection->getMethods(\ReflectionMethod::IS_STATIC))->map(function ($function) {
            return [$function->name];
        })->toArray();
    }
}
