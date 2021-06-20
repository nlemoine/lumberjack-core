<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class AdminPageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!\is_admin()) {
            return;
        }

        $admin_pages = $this->getConfig('admin-pages', []);
        foreach ($admin_pages as $page) {
            $page::register();
        }
    }
}
