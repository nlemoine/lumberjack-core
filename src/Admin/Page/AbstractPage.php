<?php

namespace Rareloop\Lumberjack\Admin\Page;

use Laminas\Diactoros\ServerRequestFactory;
use Rareloop\Lumberjack\Http\ServerRequest;

use function Symfony\Component\String\u;

abstract class AbstractPage
{
    public static function getPageSlug(): string
    {
        return (string) u(static::class)->replace('\\', '-')->lower();
    }

    public static function register(): void
    {
        $config = static::getConfig();

        \add_action('admin_menu', function () use ($config) {
            $add_page_fn = 'add_menu_page';
            if (!empty($config['parent_slug'])) {
                $add_page_fn = 'add_submenu_page';
            }
            /** @var string $hook */
            $hook = \call_user_func_array($add_page_fn, $config);
            \add_action(\sprintf('load-%s', $hook), function() {
                return call_user_func_array([static::class, 'controller'], [ServerRequest::fromRequest(ServerRequestFactory::fromGlobals())]);
            });
        }, 100);
    }

    public static function render(): void
    {
    }

    protected static function getConfigRaw(): array
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

    protected static function getPageConfig(): array
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

    public static function getUrl($query = null, array $escOptions = []): string
    {
        $url = menu_page_url(static::getPageSlug(), false);

        if ($query) {
            $url .= '&' . (is_array($query) ? http_build_query($query) : (string) $query);
        }

        return esc_url($url, ...$escOptions);
    }
}
