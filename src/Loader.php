<?php

namespace Rareloop\Lumberjack;

use Timber\Loader as TimberLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

class Loader extends TimberLoader
{
    public function __construct()
    {
    }

    public function get_loader(): LoaderInterface
    {
        $loader = new FilesystemLoader(\get_template_directory() . '/views');
        return \apply_filters('timber/loader/loader', $loader);
    }
}
