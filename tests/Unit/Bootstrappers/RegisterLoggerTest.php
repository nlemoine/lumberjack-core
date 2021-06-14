<?php

namespace Rareloop\Lumberjack\Test\Bootstrappers;

use Brain\Monkey\Functions;
use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\RegisterLogger;
use Rareloop\Lumberjack\Config;

class RegisterLoggerTest extends TestCase
{
    public function testLogObjectIsAlwaysRegistered()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application(__DIR__ . '/../');
        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertTrue($app->has('logger'));
        $this->assertSame($app->get('logger'), $app->get(LoggerInterface::class));
    }

    public function testDefaultHandlerIsInMemoryStream()
    {
        $app = new Application(__DIR__ . '/../');
        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame('php://memory', $app->get('logger')->getHandlers()[0]->getUrl());
    }

    public function testDefaultLogWarningLevelIsDebug()
    {
        $app = new Application(__DIR__ . '/../');
        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame(Logger::DEBUG, $app->get('logger')->getHandlers()[0]->getLevel());
    }

    public function testStreamIsUsedWhenPathIsSetButLoggingIsDisabled()
    {
        $app = new Application(__DIR__ . '/../');
        $config = new Config();
        $config->set('log.enabled', false);
        $config->set('log.path', 'app.log');
        $app->bind(Config::class, $config);

        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame('php://memory', $app->get('logger')->getHandlers()[0]->getUrl());
    }

    public function testLogWarningLevelCanBeSetInConfig()
    {
        $app = new Application(__DIR__ . '/../');

        $config = new Config();
        $config->set('log.level', Logger::ERROR);
        $app->bind(Config::class, $config);

        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame(Logger::ERROR, $app->get('logger')->getHandlers()[0]->getLevel());
    }

    public function testErrorLogIsUsedWhenPathIsSetToFalse()
    {
        $app = new Application('/base/path');

        $config = new Config();
        $config->set('app.logs.enabled', true);
        $config->set('app.logs.path', false);
        $app->bind(Config::class, $config);

        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertInstanceOf(AbstractHandler::class, $app->get('logger')->getHandlers()[0]);
    }

    public function testStreamIsUsedWhenPathIsSetToFalseAndEnabledIsFalse()
    {
        $app = new Application('/base/path');

        $config = new Config();
        $config->set('app.logs.enabled', false);
        $config->set('app.logs.path', false);
        $app->bind(Config::class, $config);

        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame('php://memory', $app->get('logger')->getHandlers()[0]->getUrl());
    }

    public function testLogsPathCanBeChangedByConfigVariable()
    {
        $app = new Application('/base/path');

        $config = new Config();
        $config->set('log.enabled', true);
        $config->set('log.path', '/base/new.log');
        $app->bind(Config::class, $config);

        $logger = new RegisterLogger();
        $logger->bootstrap($app);

        $this->assertSame('/base/new.log', $app->get('logger')->getHandlers()[0]->getUrl());
    }
}
