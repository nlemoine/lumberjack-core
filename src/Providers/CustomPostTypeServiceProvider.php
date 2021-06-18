<?php

namespace Rareloop\Lumberjack\Providers;

class CustomPostTypeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \add_action('init', [$this, 'registerPostTypes']);
    }

    public function registerPostTypes()
    {
        $postTypes = $this->getConfig('posttypes.register', []);
        if (empty($postTypes)) {
            return;
        }
        foreach ($postTypes as $postType) {
            $postType::register();
        }
    }
}
