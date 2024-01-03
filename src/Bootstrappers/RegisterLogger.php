<?php

namespace Rareloop\Lumberjack\Bootstrappers;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use function DI\get;

class RegisterLogger
{
    private $app;

    public function bootstrap(Application $app)
    {
        $this->app = $app;

        if (\is_admin()) {
            return;
        }
        $logger = new Logger('app');

        // If the `path` config is set to false then use the Apache/Nginx error logs
        if ($this->shouldUseErrorLogHandler()) {
            $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $this->getLogLevel());
        } else {
            $handler = new RotatingFileHandler($this->getLogsPath(), 0, $this->getLogLevel());
        }

        $logger->pushHandler($handler);

        $this->app->singleton(LoggerInterface::class, $logger);
        $this->app->singleton('logger', get(LoggerInterface::class));
    }

    private function shouldUseErrorLogHandler()
    {
        $config = false;

        // Get the config from the container if it's been registered
        if ($this->app->has(Config::class)) {
            $config = $this->app->get(Config::class);
        }

        return $config && $config->get('log.path') === false && $config->get('log.enabled') === true;
    }

    private function getLogLevel()
    {
        $logLevel = Logger::DEBUG;

        if ($this->app->has(Config::class)) {
            $logLevel = $this->app->get(Config::class)->get('log.level', $logLevel);
        }

        return $logLevel;
    }

    private function getLogsPath()
    {
        $logsPath = 'app.log';

        if ($this->app->has(Config::class)) {
            $config = $this->app->get(Config::class);

            if (!$config->get('log.enabled', false)) {
                return 'php://memory';
            }

            $logsPath = $config->get('log.path', $logsPath);
        }

        return $logsPath;
    }
}
