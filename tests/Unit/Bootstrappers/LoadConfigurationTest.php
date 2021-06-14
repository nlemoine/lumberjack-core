<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\LoadConfiguration;
use Rareloop\Lumberjack\Config;

class LoadConfigurationTest extends TestCase
{
    public function testAddsConfigObjectToTheContainer()
    {
        $app = new Application(__DIR__ . '/../');
        $bootstrapper = new LoadConfiguration();

        $bootstrapper->bootstrap($app);

        $this->assertTrue($app->has('config'));
        $this->assertInstanceOf(Config::class, $app->get('config'));
        $this->assertSame($app->get('config'), $app->get(Config::class));
    }

    public function testCreatedConfigObjectHasLoadedConfig()
    {
        $app = new Application(__DIR__ . '/../');
        $bootstrapper = new LoadConfiguration();

        $bootstrapper->bootstrap($app);
        $config = $app->get('config');

        $this->assertSame('production', $config->get('app.environment'));
    }
}
