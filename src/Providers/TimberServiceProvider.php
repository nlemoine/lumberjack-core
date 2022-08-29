<?php

namespace Rareloop\Lumberjack\Providers;

use PiedWeb\RenderAttributes\TwigExtension as RenderAttributesExtension;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Loader;
use Rareloop\Lumberjack\Models;
use Rareloop\Lumberjack\Timber;
use Rareloop\Lumberjack\Twig\Extensions\AssetExtension;
use Rareloop\Lumberjack\Twig\Extensions\SvgHelpersExtension;
use Rareloop\Lumberjack\Twig\Extensions\TextHelpersExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Timber\Timber as TimberCore;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;
use Twig\Extra\String\StringExtension;

class TimberServiceProvider extends ServiceProvider
{
    public function register()
    {
        $timber = new Timber();

        $this->app->singleton('timber', $timber);
        $this->app->singleton(TimberCore::class, $timber);

        $this->app->singleton('twig', function () {
            return (new Loader())->get_twig();
        });
        $this->app->singleton(Environment::class, function () {
            return $this->app->get('twig');
        });

        $this->app->singleton('slugger', function () {
            return new AsciiSlugger($this->app->get('locale'));
        });
    }

    public function boot(Config $config)
    {
        // Paths
        $paths = $config->get('timber.paths');
        if ($paths) {
            Timber::$dirname = $paths;
        }

        // Extensions, functions & filters
        \add_filter('timber/twig/filters', [$this, 'filterTimberFilters']);
        \add_filter('timber/twig/functions', [$this, 'filterTimberFunctions']);
        \add_filter('timber/twig', [$this, 'addTwigExtensions']);
        \add_filter('timber/cache/enable_extension', '__return_false');

        // Configure Twig
        \add_filter('timber/twig/environment/options', [$this, 'configureTwigOptions']);

        // Default classmap
        \add_filter('timber/post/classmap', [$this, 'setDefaultPostClassmap'], 1);
    }

    /**
     * Set default classmap for post
     */
    public function setDefaultPostClassmap(array $classmap): array
    {
        return \array_merge($classmap, [
            'post' => Models\Post::class,
            'page' => Models\Page::class,
        ]);
    }

    /**
     * Configure Twig options
     */
    public function configureTwigOptions(array $options): array
    {
        $default_options = [
            'strict_variables' => $options['debug'],
            'autoescape'       => 'html',
            'cache'            => $options['debug'] ? false : $this->get('path.project') . '/var/cache/twig',
        ];
        return \array_merge($options, $default_options);
    }

    /**
     * Whitelist Timber filters
     */
    public function filterTimberFilters(array $filters): array
    {
        $whitelist = [
            'array',
            'excerpt',
            'function',
            'sanitize',
            'date',
            'relative',
            'truncate',
            'time_ago',
            'apply_filters',
        ];
        return \array_intersect_key($filters, \array_flip($whitelist));
    }

    /**
     * Whitelist Timber functions
     */
    public function filterTimberFunctions(array $functions): array
    {
        $whitelist = [
            '__',
            '_n',
            'fn',
            'action',
            'get_post',
            'get_image',
            'get_attachment',
            'get_posts',
            'get_attachment_by',
            'get_term',
            'get_terms',
            'get_user',
            'get_comment',
            'get_comments',
            'Post',
            'TimberPost',
            'Image',
            'TimberImage',
            'Term',
            'TimberTerm',
            'User',
            'TimberUser',
            'shortcode',
            'bloginfo',
        ];
        return \array_intersect_key($functions, \array_flip($whitelist));
    }

    /**
     * Add Twig extensions
     */
    public function addTwigExtensions(Environment $twig): Environment
    {
        $twig->addExtension(new HtmlExtension());
        $twig->addExtension(new RenderAttributesExtension());
        $twig->addExtension(new TextHelpersExtension());
        $twig->addExtension(new StringExtension($this->has('slugger') ? $this->get('slugger') : null));

        if ($this->has(Packages::class)) {
            $packages = $this->get(Packages::class);
            $twig->addExtension(new AssetExtension($packages));
            try {
                $pathPackage = $packages->getPackage('images_path');
                $twig->addExtension(new SvgHelpersExtension($pathPackage));
            } catch (\Exception $e) {
                if (WP_DEBUG) {
                    throw $e;
                }
            }
        }

        return $twig;
    }
}
