<?php

namespace Rareloop\Lumberjack\Router\Symfony\Loader;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class ArrayLoader extends YamlFileLoader
{
    public function __construct(
        private array $prefixes,
        string $env = null
    ) {
    }

    /**
     * @throws \InvalidArgumentException When a route can't be parsed because YAML is invalid
     */
    public function load(mixed $config, string $type = null): RouteCollection
    {
        $collection = new RouteCollection();

        // empty file
        if (empty($config)) {
            return $collection;
        }

        foreach ($config as $name => $config) {
            $this->validate($config, $name, '');
            $this->parseRoute($collection, $name, $config, '');
        }
        $collection->setSchemes([\parse_url(\home_url(), PHP_URL_SCHEME)]);

        return $collection;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return \is_array($resource);
    }

    // protected function parseRoute(RouteCollection $collection, string $name, array $config, string $path = 'routes.php') {
    //     parent::parseRoute($collection, $name, $config, $path);
    //     $this->createLocalizedRoute($collection, $name, $config['path'], '', $this->prefixes);
    // }

    /**
     * Parses a route and adds it to the RouteCollection.
     */
    protected function parseRoute(RouteCollection $collection, string $name, array $config, string $path)
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
                throw new \InvalidArgumentException(\sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s"?', $placeholder, $requirement, $name, $path));
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

        $routes = $this->createLocalizedRoute($collection, $name, $config['path'], '', $this->prefixes);
        $routes->addDefaults($defaults);
        $routes->addRequirements($requirements);
        $routes->addOptions($options);
        $routes->setSchemes($config['schemes'] ?? []);
        $routes->setMethods($config['methods'] ?? []);
        $routes->setCondition($config['condition'] ?? null);

        if (isset($config['host'])) {
            $this->addHost($routes, $config['host']);
        }
    }
}
