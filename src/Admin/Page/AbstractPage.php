<?php

namespace Rareloop\Lumberjack\Admin\Page;

use InvalidArgumentException;

abstract class AbstractPage {
    public static function getPageSlug(): string {
        return sanitize_title(str_replace('\\', '-', static::class));
    }

    public static function register(): void {
        if (!is_admin()) {
            return;
        }
        add_action('admin_menu', array(static::class, 'addMenuPage'));
    }

    public static function addMenuPage() {
        // Use the right function depending on config
        $addPageFn = 'add_menu_page';
        if (!empty($config['parent_slug'])) {
            $addPageFn = 'add_submenu_page';
        }
        $config = static::getConfig();
        $hook = call_user_func_array($addPageFn, $config);
        add_action(sprintf('load-%s', $hook), array(static::class, 'runHooks'));
        add_action(sprintf('load-%s', $hook), array(static::class, 'controller'));

        if (self::isModal()) {
            add_action(sprintf('load-%s', $hook), function() {
                self::runModalHooks();
            });
        }
    }

    public static function runHooks(): void {
        add_action('admin_enqueue_scripts', array(static::class, 'enqueueAssets'));
        add_action('admin_print_styles', array(static::class, 'printSyles'));
        add_action('admin_print_scripts', array(static::class, 'printScripts'));
    }

    private static function runModalHooks(): void {
        // Hides admin bar & other stuff
        define('IFRAME_REQUEST', true);
        // Remove admin notices
        remove_all_actions('admin_notices');
        // Empty menu
        global $menu;
        $menu = array();
        // Hide things we don't want on modal
        add_action('admin_print_styles', function() {
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
     * Used to run early stuff like `pre_get_posts` or other logic
     *
     * @return void
     */
    public static function controller(): void {
    }

    /**
     * Enqueue your scripts & styles for this page
     *
     * @return void
     */
    public static function enqueueAssets(): void {
    }

    /**
     * Insert your inline scripts in <head>
     *
     * @return void
     */
    public static function printScripts(): void {
    }

    /**
     * Insert your inline styles in <head>
     *
     * @return void
     */
    public static function printSyles(): void {
    }

    /**
     * Render your admin page
     *
     * @return void
     */
    public static function renderPage(array $config): void {
    }

    /**
     * Render your admin page as a modal
     *
     * @return void
     */
    public static function renderModal(array $config): void {
    }

    /**
     * Render dispatcher
     *
     * @param array $config
     * @param boolean $isModal
     * @return void
     */
    private static function renderAdminPage(array $config, bool $isModal): void {
        if ($isModal) {
            static::renderModal($config);
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . $config['page_title'] . '</h1>';
        static::renderPage($config);
        echo '</div>';
    }

    protected static function getConfig(): array {
        $pageConfig = static::getPageConfig();
        $config = array_merge(static::getDefaultConfig(), $pageConfig);

        if (empty($config['page_title'])) {
            // This is the minimum required config
            throw new InvalidArgumentException('page_title is required to register the page');
        }

        $isModal = self::isModal();
        $config['function'] = function() use ($config, $isModal) {
            call_user_func_array(array(static::class, 'renderAdminPage'), array($config, $isModal));
        };

        $isTopLevelPage = empty($config['parent_slug']);

        $allowedKeys = static::getConfigKeys();
        if ($isTopLevelPage) {
            array_splice($allowedKeys, 5, 0, array('icon_url'));
        } else {
            array_unshift($allowedKeys, 'parent_slug');
        }

        $config = array_filter($config, function ($key) use ($allowedKeys) {
            return in_array($key, $allowedKeys, true);
        }, ARRAY_FILTER_USE_KEY);

        // Fallback on page title
        if (empty($config['menu_title'])) {
            $config['menu_title'] = $config['page_title'];
        }

        $configOrdered = array();
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

    protected static function getConfigKeys(): array {
        return array(
            'page_title',
            'menu_title',
            'capability',
            'menu_slug',
            'function',
            'position',
        );
    }

    protected static function getDefaultConfig(): array {
        return array(
            'capability' => 'edit_posts',
            'menu_slug' => static::getPageSlug(),
            'icon_url' => '',
            'position' => null,
        );
    }

    public static function getUrl($query = null, array $escOptions = array()): string {
        $url = menu_page_url(static::getPageSlug(), false);

        if ($query) {
            $url .= '&' . (is_array($query) ? http_build_query($query) : (string) $query);
        }

        return esc_url($url, ...$escOptions);
    }

    public static function getModalUrl(array $modalArgs = array()): string {
        // Ensure iframe args comes last
        return static::getUrl(array_merge(array(
            'modal_window' => 1,
        ), $modalArgs, array(
            'TB_iframe' => 'true',
            'width' => 600,
            'height' => 550,
        )));
    }

    protected static function isModal(): bool {
        return !empty($_REQUEST['modal_window']);
    }
}
