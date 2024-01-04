<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Providers\ServiceProvider;

class LocoTranslateServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!\is_admin()) {
            return;
        }
        if (\defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
            // Allow file mods for languages
            \add_filter('file_mod_allowed', [$this, 'allow_file_mod'], 10, 2);
        }
        \add_action('admin_menu', [$this, 'remove_menus'], 50);
    }

    public function remove_menus()
    {
        \remove_submenu_page('loco', 'loco-core');
        \remove_submenu_page('loco', 'loco-plugin');
        \remove_submenu_page('loco', 'loco-lang');
    }

    public function allow_file_mod($allowed, $context)
    {
        return \in_array($context, ['download_language_pack', 'can_install_language_pack'], true);
    }
}
