<?php

namespace Rareloop\Lumberjack\Test\Providers;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Providers\ServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class ServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testCanMergeConfigFromAFile()
    {
        $config = new Config();
        $app = new Application();
        $app->bind(Config::class, $config);
        $provider = new TestServiceProvider($app);

        $provider->mergeConfigFrom(__DIR__ . '/../config/another.php', 'another');

        $this->assertSame(123, $config->get('another.test'));
    }

    public function testExistingConfigTakesPriorityOverMergedValues()
    {
        $config = new Config();
        $app = new Application();
        $app->bind(Config::class, $config);
        $provider = new TestServiceProvider($app);

        $config->set('another.test', 456);
        $provider->mergeConfigFrom(__DIR__ . '/../config/another.php', 'another');

        $this->assertSame(456, $config->get('another.test'));
    }
}

class TestServiceProvider extends ServiceProvider
{
}
