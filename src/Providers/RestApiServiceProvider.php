<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class RestApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('rest_api_init', [$this, 'registerRestControllers']);
        \add_filter('rest_url_prefix', [$this, 'setPrefix']);
        // \add_filter('rest_endpoints', [$this, 'filterEndpoints']);
    }

    public function filterEndpoints($endpoints): array
    {
        $endpoints_whitelist = $this->app->get(Config::class)->get('rest-api.endpoints', []);
        if (empty($endpoints_whitelist)) {
            return $endpoints;
        }

        return \array_filter($endpoints, function () {
            return true;
        });
    }

    public function registerRestControllers(): void
    {
        $controllers = $this->app->get(Config::class)->get('rest-api.controllers', []);
        foreach ($controllers as $controller) {
            $controller = new $controller();
            $controller->register_routes();
        }
    }

    public function setPrefix(string $prefix): string
    {
        $customPrefix = $this->app->get(Config::class)->get('rest-api.prefix', null);
        return $customPrefix ?? $prefix;
    }
}
