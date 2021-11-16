<?php

namespace Rareloop\Lumberjack\Providers;

use DeliciousBrains\WPMigrations\Database\Migrator;

class MigrationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!\class_exists('WP_CLI')) {
            return;
        }

        \add_filter('dbi_wp_migrations_path', function () {
            return $this->app->get('path.theme') . '/app/Migrations';
        });

        Migrator::instance();
    }
}
