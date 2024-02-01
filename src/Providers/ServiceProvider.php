<?php

namespace Rareloop\Lumberjack\Providers;

use InvalidArgumentException;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;

abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Merge the config in the provided path into what already exists. Existing config takes
     * priority over what is found in $path.
     *
     * @param  string $path
     * @param  string $key
     */
    public function mergeConfigFrom($path, $key)
    {
        $existing = $this->getConfig($key, []);
        $this->get(Config::class)->set($key, \array_merge(require $path, $existing));
    }

    protected function get(string $key)
    {
        return $this->app->get($key);
    }

    protected function has(string $key)
    {
        return $this->app->has($key);
    }

    protected function getConfig(string $key, $default = null)
    {
        return $this->get(Config::class)->get($key, $default);
    }

    protected function getParameter($parameter)
    {
        if ($parameter === null) {
            return null;
        }
        return $this->resolveValue($parameter);
    }

    protected function resolveValue($value)
    {
        if (\is_array($value)) {
            return \array_map(function ($value) {
                return $this->resolveValue($value);
            }, $value);
        }

        if (\strpos($value, '%') === false || !\is_string($value)) {
            return $this->resolveAlias($value);
        }

        return \preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($value) {
            if (empty($match[1])) {
                return $value;
            }

            $resolved = $this->resolveAlias($match[1]);

            if (!\is_string($resolved) && !\is_numeric($resolved)) {
                throw new InvalidArgumentException(\sprintf('The parameter "%s" must be a string or numeric, but was of type "%s".', $match[0], \gettype($resolved)));
            }

            return $resolved;
        }, $value);
    }

    protected function resolveAlias($alias)
    {
        if (\is_array($alias)) {
            return \array_map(function ($alias) {
                return $this->resolveAlias($alias);
            }, $alias);
        }

        if (!$this->has($alias)) {
            return $alias;
        }
        return $this->get($alias);
    }
}
