<?php

namespace Rareloop\Lumberjack\Admin\Option;

use Rareloop\Lumberjack\Admin\Page\AbstractPage;

abstract class AbstractAcfOptionPage extends AbstractPage
{
    public static function register(): void
    {
        if (!\function_exists('acf_add_options_page')) {
            return;
        }

        \add_action('acf/init', [static::class, 'addAcfOptionPage']);
    }

    public static function addAcfOptionPage()
    {
        $config = static::getConfig();
        $addPageFn = 'acf_add_options_page';
        if (!empty($config['parent_slug'])) {
            $addPageFn = 'acf_add_options_sub_page';
        }
        $addPageFn($config);
    }

    /**
     * @see https://www.advancedcustomfields.com/resources/acf_add_options_page/
     */
    protected static function getConfigKeys(): array
    {
        return \array_merge(parent::getConfigKeys(), [
            'redirect',
            'post_id',
            'autoload',
            'update_button',
            'updated_message',
        ]);
    }

    protected static function getDefaultConfig(): array
    {
        return \array_merge(parent::getDefaultConfig(), [
            'autoload'        => false,
            'update_button'   => \__('Save'),
            'updated_message' => \__('Saved'),
        ]);
    }
}
