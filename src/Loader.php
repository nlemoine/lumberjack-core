<?php

namespace Rareloop\Lumberjack;

use Timber\Loader as TimberLoader;
use Twig\Loader\FilesystemLoader;

class Loader extends TimberLoader
{
    public function __construct()
    {
    }

    public function get_loader()
    {
        $loader = new FilesystemLoader(\get_template_directory() . '/views');
        return $loader;
    }
}
