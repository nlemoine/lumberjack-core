<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Providers\ServiceProvider;

class BlockServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action('acf/init', [$this, 'registerAcfBlocks']);
    }

    public function registerAcfBlocks()
    {
        $blocks = $this->getConfig('blocks', []);
        if (empty($blocks)) {
            return;
        }
        foreach($blocks as $block) {
            $block::register();
        }
    }
}
