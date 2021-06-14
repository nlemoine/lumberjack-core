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

        $config = static::getConfigRaw();

        \add_action('acf/init', function () use ($config) {
            $is_top_level_page = empty($config['parent_slug']);
            if ($is_top_level_page) {
                $hook = \acf_add_options_page($config);
            } else {
                $hook = \acf_add_options_sub_page($config);
            }
        });
    }

    protected static function getDefaultConfig(): array
    {
        return \array_merge(parent::getDefaultConfig(), [
            'autoload'        => true,
            'update_button'   => \__('Save'),
            'updated_message' => \__('Saved'),
        ]);
    }
}
