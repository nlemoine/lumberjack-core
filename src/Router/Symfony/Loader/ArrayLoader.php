<?php

namespace Rareloop\Lumberjack\Router\Symfony\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\Configurator\Traits\HostTrait;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ArrayLoader extends Loader
{
    use HostTrait;

    private const AVAILABLE_KEYS = [
        'resource', 'type', 'prefix', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options', 'condition', 'controller', 'name_prefix', 'trailing_slash_on_root', 'locale', 'format', 'utf8', 'exclude', 'stateless',
    ];

    /**
     * @throws \InvalidArgumentException When a route can't be parsed because YAML is invalid
     */
    public function load(mixed $config, string $type = null): RouteCollection
    {
        $collection = new RouteCollection();

        // empty file
        if ($config === null) {
            return $collection;
        }

        foreach ($config as $name => $config) {
            $this->validate($config, $name);
            $this->parseRoute($collection, $name, $config);
        }

        return $collection;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return \is_array($resource);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     */
    protected function parseRoute(RouteCollection $collection, string $name, array $config)
    {
        if (isset($config['alias'])) {
            $alias = $collection->addAlias($name, $config['alias']);
            $deprecation = $config['deprecated'] ?? null;
            if ($deprecation !== null) {
                $alias->setDeprecated(
                    $deprecation['package'],
                    $deprecation['version'],
                    $deprecation['message'] ?? ''
                );
            }

            return;
        }

        $defaults = $config['defaults'] ?? [];
        $requirements = $config['requirements'] ?? [];
        $options = $config['options'] ?? [];

        foreach ($requirements as $placeholder => $requirement) {
            if (\is_int($placeholder)) {
                throw new \InvalidArgumentException(\sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s"?', $placeholder, $requirement, $name));
            }
        }

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }
        if (isset($config['locale'])) {
            $defaults['_locale'] = $config['locale'];
        }
        if (isset($config['format'])) {
            $defaults['_format'] = $config['format'];
        }
        if (isset($config['utf8'])) {
            $options['utf8'] = $config['utf8'];
        }
        if (isset($config['stateless'])) {
            $defaults['_stateless'] = $config['stateless'];
        }

        $routes = $this->createLocalizedRoute($collection, $name, $config['path']);

        $routes->addDefaults($defaults);
        $routes->addRequirements($requirements);
        $routes->addOptions($options);
        $routes->setSchemes([\parse_url(\home_url(), PHP_URL_SCHEME)]);
        $routes->setMethods($config['methods'] ?? []);
        $routes->setCondition($config['condition'] ?? null);

        $this->addHost($routes, \parse_url(\home_url(), PHP_URL_HOST));
    }

    /**
     * Creates one or many routes.
     *
     * @param string|array $path the path, or the localized paths of the route
     */
    protected function createLocalizedRoute(RouteCollection $collection, string $name, string|array $path, string $namePrefix = '', array $prefixes = null): RouteCollection
    {
        $paths = [];

        $routes = new RouteCollection();

        if (\function_exists('PLL')) {
            $pll = \PLL();
            $hideDefault = $pll->options['hide_default'] ?? false;
            $languages = $pll->model->get_languages_list([
                'fields' => 'slug',
            ]);

            $prefixes = \array_combine($languages, \array_map(fn ($l) => '/' . $l, $languages));
            if ($hideDefault) {
                $defaultLang = $pll->options['default_lang'] ?? null;
                if (isset($prefixes[$defaultLang])) {
                    $prefixes[$defaultLang] = '';
                }
            }
        }

        if (\is_array($path)) {
            if ($prefixes === null) {
                $paths = $path;
            } elseif ($missing = \array_diff_key($prefixes, $path)) {
                throw new \LogicException(\sprintf('Route "%s" is missing routes for locale(s) "%s".', $name, \implode('", "', \array_keys($missing))));
            } else {
                foreach ($path as $locale => $localePath) {
                    if (!isset($prefixes[$locale])) {
                        throw new \LogicException(\sprintf('Route "%s" with locale "%s" is missing a corresponding prefix in its parent collection.', $name, $locale));
                    }

                    $paths[$locale] = $prefixes[$locale] . $localePath;
                }
            }
        } elseif ($prefixes !== null) {
            foreach ($prefixes as $locale => $prefix) {
                $paths[$locale] = $prefix . $path;
            }
        } else {
            $routes->add($namePrefix . $name, $route = $this->createRoute($path));
            $collection->add($namePrefix . $name, $route);

            return $routes;
        }

        foreach ($paths as $locale => $path) {
            $routes->add($name . '.' . $locale, $route = $this->createRoute($path));
            $collection->add($namePrefix . $name . '.' . $locale, $route);
            $route->setDefault('_locale', $locale);
            $route->setRequirement('_locale', \preg_quote($locale));
            $route->setDefault('_canonical_route', $namePrefix . $name);
        }

        return $routes;
    }

    /**
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    protected function validate(mixed $config, string $name)
    {
        if (!\is_array($config)) {
            throw new \InvalidArgumentException(\sprintf('The definition of "%s" must be an array.', $name));
        }
        if (isset($config['alias'])) {
            $this->validateAlias($config, $name);

            return;
        }
        if ($extraKeys = \array_diff(\array_keys($config), self::AVAILABLE_KEYS)) {
            throw new \InvalidArgumentException(\sprintf('The routingcontains unsupported keys for "%s": "%s". Expected one of: "%s".', $name, \implode('", "', $extraKeys), \implode('", "', self::AVAILABLE_KEYS)));
        }
        if (isset($config['resource']) && isset($config['path'])) {
            throw new \InvalidArgumentException(\sprintf('The routingmust not specify both the "resource" key and the "path" key for "%s". Choose between an import and a route definition.', $name));
        }
        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(\sprintf('The "type" key for the route definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.', $name));
        }
        if (!isset($config['resource']) && !isset($config['path'])) {
            throw new \InvalidArgumentException(\sprintf('You must define a "path" for the route "%s".', $name));
        }
        if (isset($config['controller']) && isset($config['defaults']['_controller'])) {
            throw new \InvalidArgumentException(\sprintf('The routingmust not specify both the "controller" key and the defaults key "_controller" for "%s".', $name));
        }
        if (isset($config['stateless']) && isset($config['defaults']['_stateless'])) {
            throw new \InvalidArgumentException(\sprintf('The routingmust not specify both the "stateless" key and the defaults key "_stateless" for "%s".', $name));
        }
    }

    private function createRoute(string $path): Route
    {
        return new Route($path);
    }

    /**
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    private function validateAlias(array $config, string $name): void
    {
        foreach ($config as $key => $value) {
            if (!\in_array($key, ['alias', 'deprecated'], true)) {
                throw new \InvalidArgumentException(\sprintf('The routing must not specify other keys than "alias" and "deprecated" for "%s".', $name));
            }

            if ($key === 'deprecated') {
                if (!isset($value['package'])) {
                    throw new \InvalidArgumentException(\sprintf('The routing must specify the attribute "package" of the "deprecated" option for "%s".', $name));
                }

                if (!isset($value['version'])) {
                    throw new \InvalidArgumentException(\sprintf('The routing must specify the attribute "version" of the "deprecated" option for "%s".', $name));
                }
            }
        }
    }
}
