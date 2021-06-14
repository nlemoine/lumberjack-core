<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class AdminPageServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        if (!\is_admin()) {
            return;
        }

        $admin_pages = $config->get('admin-pages', []);
        if (empty($admin_pages)) {
            return;
        }
        foreach ($admin_pages as $page) {
            $page::register();
        }
    }
}
