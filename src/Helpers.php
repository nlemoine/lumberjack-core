<?php

namespace Rareloop\Lumberjack;

use Exception;
use Rareloop\Lumberjack\Contracts\ExceptionHandler as ExceptionHandlerContract;
use Rareloop\Lumberjack\Facades\Config;
use Rareloop\Lumberjack\Facades\Log;
use Rareloop\Lumberjack\Facades\Router;
use Rareloop\Lumberjack\Facades\Session;
use Rareloop\Lumberjack\Http\Responses\RedirectResponse;

class Helpers
{
    public static function app($key = null)
    {
        $app = $GLOBALS['__app__'];

        if ($key === null) {
            return $app;
        }

        return $app->get($key);
    }

    public static function config($key, $default = null)
    {
        if (\is_array($key)) {
            $keyValues = $key;

            foreach ($keyValues as $key => $value) {
                Config::set($key, $value);
            }

            return;
        }

        return Config::get($key, $default);
    }

    public static function route($name, $params = [])
    {
        return Router::url($name, $params);
    }

    public static function request()
    {
        return static::app('request');
    }

    public static function logger($message = null, $context = [])
    {
        if ($message === null) {
            return static::app('logger');
        }

        return Log::debug($message, $context);
    }
}
