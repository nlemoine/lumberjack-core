<?php

namespace App\Providers;

use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Providers\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
    public function boot(Config $config)
    {
        $default_sizes = $config->get('images.default-sizes', []);
        if (empty($default_sizes)) {
            return;
        }

        foreach ($default_sizes as $size) {
            $name = $size['name'] ?? null;
            if (!$name) {
                continue;
            }
            \add_filter("pre_option_{$name}_size_w", function ($option) use ($size) {
                return $size['width'];
            });
            \add_filter("pre_option_{$name}_size_h", function ($option) use ($size) {
                return $size['height'];
            });
            \add_filter("pre_option_{$name}_crop", function ($option) use ($size) {
                return $size['crop'] ?? 0;
            });
        }
    }
}
