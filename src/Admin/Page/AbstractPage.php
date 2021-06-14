<?php

namespace Rareloop\Lumberjack\Admin\Page;

abstract class AbstractPage
{
    public static function getPageSlug()
    {
        return null;
    }

    public static function register()
    {
        $config = static::getConfig();

        \add_action('admin_menu', function () use ($config) {
            if (empty($config['parent_slug'])) {
                $hook = \call_user_func_array('add_menu_page', $config);
            } else {
                $hook = \call_user_func_array('add_submenu_page', $config);
            }
            \add_action(\sprintf('load-%s', $hook), [static::class, 'controller']);
        }, 100);
    }

    public static function render()
    {
    }

    protected static function getConfigRaw()
    {
        $config = static::getPageConfig();
        $default_config = static::getDefaultConfig();
        $config = \array_merge($default_config, $config);

        return $config;
    }

    protected static function getConfig(): array
    {
        $config = static::getConfigRaw();

        $is_top_level_page = empty($config['parent_slug']);

        // add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
        // add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
        $allowed_keys = [
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            'function',
            'position',
        ];

        if ($is_top_level_page) {
            \array_splice($allowed_keys, 5, 0, ['icon_url']);
        } else {
            \array_unshift($allowed_keys, 'parent_slug');
        }

        $config = \array_filter($config, function ($key) use ($allowed_keys) {
            return \in_array($key, $allowed_keys, true);
        }, ARRAY_FILTER_USE_KEY);

        $config_ordered = [];

        foreach ($allowed_keys as $key) {
            foreach ($config as $k => $value) {
                if ($k !== $key) {
                    continue;
                }
                $config_ordered[$key] = $value;
            }
        }

        return $config_ordered;
    }

    protected static function getPageConfig()
    {
        return [];
    }

    protected static function getDefaultConfig(): array
    {
        return [
            'capability' => 'edit_posts',
            'function'   => [static::class, 'render'],
            'icon_url'   => '',
            'position'   => null,
        ];
    }

    protected static function getUrl(): string
    {
        return \menu_page_url(static::getPageSlug(), false);
    }
}
