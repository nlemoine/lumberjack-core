<?php

namespace Rareloop\Lumberjack\Admin\Page;

use InvalidArgumentException;

abstract class AbstractPage
{
    public static function getPageSlug(): string
    {
        return \sanitize_title(\str_replace('\\', '-', static::class));
    }

    public static function register(): void
    {
        if (!\is_admin()) {
            return;
        }
        \add_action('admin_menu', [static::class, 'addMenuPage']);
    }

    public static function addMenuPage()
    {
        $config = static::getConfig();

        // Use the right function depending on config
        $addPageFn = 'add_menu_page';
        if (!empty($config['parent_slug'])) {
            $addPageFn = 'add_submenu_page';
        }
        $hook = \call_user_func_array($addPageFn, $config);
        \add_action(\sprintf('load-%s', $hook), [static::class, 'runHooks']);
        \add_action(\sprintf('load-%s', $hook), [static::class, 'controller']);

        if (self::isModal()) {
            \add_action(\sprintf('load-%s', $hook), function () {
                self::runModalHooks();
            });
        }
    }

    public static function runHooks(): void
    {
        \add_action('admin_enqueue_scripts', [static::class, 'enqueueAssets']);
        \add_action('admin_print_styles', [static::class, 'printSyles']);
        \add_action('admin_print_scripts', [static::class, 'printScripts']);
    }

    /**
     * Used to run early stuff like `pre_get_posts` or other logic
     */
    public static function controller(): void
    {
    }

    /**
     * Enqueue your scripts & styles for this page
     */
    public static function enqueueAssets(): void
    {
    }

    /**
     * Insert your inline scripts in <head>
     */
    public static function printScripts(): void
    {
    }

    /**
     * Insert your inline styles in <head>
     */
    public static function printSyles(): void
    {
    }

    /**
     * Render your admin page
     */
    public static function renderPage(array $config): void
    {
    }

    /**
     * Render your admin page as a modal
     */
    public static function renderModal(array $config): void
    {
    }

    public static function getUrl($query = null, array $escOptions = []): string
    {
        $url = \menu_page_url(static::getPageSlug(), false);

        if ($query) {
            $url .= '&' . (\is_array($query) ? \http_build_query($query) : (string) $query);
        }

        return \esc_url($url, ...$escOptions);
    }

    public static function getModalUrl(array $modalArgs = []): string
    {
        // Ensure iframe args comes last
        return static::getUrl(\array_merge([
            'modal_window' => 1,
        ], $modalArgs, [
            'TB_iframe' => 'true',
            'width'     => 600,
            'height'    => 550,
        ]));
    }

    protected static function getConfig(): array
    {
        $pageConfig = static::getPageConfig();
        $config = \array_merge(static::getDefaultConfig(), $pageConfig);

        if (empty($config['page_title'])) {
            // This is the minimum required config
            throw new InvalidArgumentException('page_title is required to register the page');
        }

        $isModal = self::isModal();
        $config['callback'] = function () use ($config, $isModal) {
            \call_user_func_array([static::class, 'renderAdminPage'], [$config, $isModal]);
        };

        $isTopLevelPage = empty($config['parent_slug']);

        $allowedKeys = static::getConfigKeys();
        if ($isTopLevelPage) {
            \array_splice($allowedKeys, 5, 0, ['icon_url']);
        } else {
            \array_unshift($allowedKeys, 'parent_slug');
        }

        $config = \array_filter($config, function ($key) use ($allowedKeys) {
            return \in_array($key, $allowedKeys, true);
        }, ARRAY_FILTER_USE_KEY);

        // Fallback on page title
        if (empty($config['menu_title'])) {
            $config['menu_title'] = $config['page_title'];
        }

        $configOrdered = [];
        foreach ($allowedKeys as $key) {
            foreach ($config as $k => $value) {
                if ($k !== $key) {
                    continue;
                }
                $configOrdered[$key] = $value;
            }
        }

        return $configOrdered;
    }

    abstract protected static function getPageConfig(): array;

    protected static function getConfigKeys(): array
    {
        return [
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            'callback',
            'position',
        ];
    }

    protected static function getDefaultConfig(): array
    {
        return [
            'capability' => 'edit_posts',
            'menu_slug'  => static::getPageSlug(),
            'icon_url'   => '',
            'position'   => null,
        ];
    }

    protected static function isModal(): bool
    {
        return !empty($_REQUEST['modal_window']);
    }

    private static function runModalHooks(): void
    {
        // Hides admin bar & other stuff
        \define('IFRAME_REQUEST', true);
        // Remove admin notices
        \remove_all_actions('admin_notices');
        // Empty menu
        global $menu;
        $menu = [];
        // Hide things we don't want on modal
        \add_action('admin_print_styles', function () {
            ?>
            <style>
                #wpfooter,
                #adminmenumain,
                #wpadminbar,
                #screen-meta,
                #screen-meta-links {
                    display: none !important;
                }
                html.wp-toolbar {
                    padding-top: 0;
                }
                #wpcontent {
                    margin-left: 0 !important;
                    padding-right: 20px;
                }
            </style>
            <?php
        });
    }

    /**
     * Render dispatcher
     *
     * @param boolean $isModal
     */
    private static function renderAdminPage(array $config, bool $isModal): void
    {
        if ($isModal) {
            static::renderModal($config);
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . $config['page_title'] . '</h1>';
        static::renderPage($config);
        echo '</div>';
    }
}
