<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class PolylangServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_filter('pll_get_post_types', [$this, 'registerPostTypes'], 10, 2);
    }

    public function registerPostTypes(array $post_types, bool $is_settings): array
    {
        $postTypes = $this->get(Config::class)->get('posttypes.register', []);

        foreach ($postTypes as $postType) {
            if ($is_settings) {
                unset($post_types[$postType::getPostType()]);
            } else {
                $post_types[$postType::getPostType()] = $postType::getPostType();
            }
        }

        return $post_types;
    }
}
