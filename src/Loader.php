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
        $defaultViewsPath = apply_filters('app/timber/views/default', \get_template_directory() . '/views');
        $loader = new FilesystemLoader($defaultViewsPath);
        return \apply_filters('timber/loader/loader', $loader);
    }
}
