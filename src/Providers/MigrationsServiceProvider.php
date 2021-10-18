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
        Migrator::instance();
    }
}
