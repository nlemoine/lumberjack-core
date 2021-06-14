<?php

namespace Rareloop\Lumberjack\Test;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Config;

class ConfigTest extends TestCase
{
    public function testConfigValuesCanBeSetAndGet()
    {
        $config = new Config();

        $config->set('app.environment', 'production');

        $this->assertSame('production', $config->get('app.environment'));
    }

    public function testGetReturnsDefaultWhenNoValueIsSet()
    {
        $config = new Config();

        $this->assertNull($config->get('app.environment'));
        $this->assertSame('production', $config->get('app.environment', 'production'));
    }

    public function testGetIgnoresDefaultWhenValueIsSet()
    {
        $config = new Config();

        $config->set('app.environment', 'production');

        $this->assertSame('production', $config->get('app.environment', 'staging'));
    }

    public function testGetReturnsDefaultWhenUsingDotNotationButNotAnArray()
    {
        $config = new Config();

        $config->set('app.logs', 'app.log');

        $this->assertSame(false, $config->get('app.logs.enabled', false));
    }

    public function testSetIsChainable()
    {
        $config = new Config();

        $this->assertSame($config, $config->set('app.environment', 'production'));
    }

    public function testCanReadConfigFromFiles()
    {
        $config = new Config();

        $config->load(__DIR__ . '/config');

        $this->assertSame('production', $config->get('app.environment'));
        $this->assertSame(true, $config->get('app.multi.level'));
        $this->assertSame(123, $config->get('another.test'));
    }

    public function testCanReadConfigFromFilesInConstructor()
    {
        $config = new Config(__DIR__ . '/config');

        $this->assertSame('production', $config->get('app.environment'));
        $this->assertSame(true, $config->get('app.multi.level'));
        $this->assertSame(123, $config->get('another.test'));
    }

    public function testReadIsChainable()
    {
        $config = new Config();

        $this->assertSame($config, $config->load(__DIR__ . '/config'));
    }

    public function testConfigValuesCanBeCheckedForExistence()
    {
        $config = new Config();

        $config->set('app.environment', 'production');
        $config->set('app.null', null);
        $config->set('app.false', false);

        $this->assertTrue($config->has('app.environment'));
        $this->assertTrue($config->has('app'));
        $this->assertTrue($config->has('app.false'));
        $this->assertTrue($config->has('app.null'));

        $this->assertFalse($config->has('app.nope'));
        $this->assertFalse($config->has('nope.nope'));
    }
}
