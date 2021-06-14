<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;

class CustomPostTypeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('init', [$this, 'registerPostTypes']);
    }

    public function registerPostTypes()
    {
        $postTypes = $this->get(Config::class)->get('posttypes.register', []);
        if (empty($postTypes)) {
            return;
        }
        foreach ($postTypes as $postType) {
            $postType::register();
        }
    }
}
