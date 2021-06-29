<?php

namespace Rareloop\Lumberjack\Providers;

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
