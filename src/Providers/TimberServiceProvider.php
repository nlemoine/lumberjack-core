<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Timber;
use Rareloop\Lumberjack\Twig\Extensions\AssetExtension;
use Rareloop\Lumberjack\Twig\Extensions\RoutingExtension;
use Rareloop\Lumberjack\Twig\Extensions\SvgHelpersExtension;
use Rareloop\Lumberjack\Twig\Extensions\TextHelpersExtension;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Timber\Loader;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\LoaderInterface;

class TimberServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Timber::$context_cache = true;
        $timber = new Timber();

        $this->app->singleton('timber', $timber);
        $this->app->singleton(Timber::class, $timber);

        $this->app->singleton('twig', function () {
            return (new Loader())->get_twig();
        });

        $this->app->singleton('slugger', function () {
            return new AsciiSlugger($this->app->get('locale'));
        });
    }

    public function boot(Config $config)
    {
        // Cache
        Timber::$cache = !$config->get('app.debug');
        // Autoescape
        Timber::$autoescape = $config->get('timber.autoescape', true);
        // Paths
        $paths = $config->get('timber.paths');
        if ($paths) {
            Timber::$dirname = $paths;
        }

        // Add Symfony form theme path
        \add_filter('timber/loader/loader', [$this, 'addSymfonyFormThemePath']);

        // Set cache location
        \add_filter('timber/cache/location', [$this, 'setCacheLocation']);

        // Add extensions
        \add_filter('timber/loader/twig', [$this, 'addTwigExtensions']);

        // Configure Twig
        \add_filter('timber/loader/twig', [$this, 'configureTwig']);
    }

    public function configureTwig(Environment $twig)
    {
        if (WP_DEBUG) {
            $twig->enableStrictVariables();
        }
        return $twig;
    }

    /**
     * Add Symfony form theme path
     */
    public function addSymfonyFormThemePath(LoaderInterface $loader): LoaderInterface
    {
        $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
        $vendorTwigBridgeDirectory = \dirname($appVariableReflection->getFileName());
        $loader->addPath($vendorTwigBridgeDirectory . '/Resources/views/Form');

        return $loader;
    }

    /**
     * Set cache location
     */
    public function setCacheLocation(string $location): string
    {
        return $this->get('path.project') . '/var/cache/twig';
    }

    /**
     * Add Twig extensions
     */
    public function addTwigExtensions(Environment $twig): Environment
    {
        $twig->addExtension(new HtmlExtension());
        $twig->addExtension(new TextHelpersExtension());
        if ($this->app->has('slugger')) {
            $twig->addExtension(new StringExtension($this->get('slugger')));
        }

        if ($this->app->has('router.generator')) {
            $twig->addExtension(new RoutingExtension($this->get('router.generator')));
        }

        if ($this->app->has('assets.packages')) {
            $packages = $this->get('assets.packages');
            $twig->addExtension(new AssetExtension($packages));
            if ($packages->getPackage('path')) {
                $twig->addExtension(new SvgHelpersExtension($packages->getPackage('path')));
            }
        }

        // $twig->addExtension(
        //     new ImageFactoryExtension($this->app->get('image.factory'))
        // );

        // $twig->addExtension(new TranslationExtension());

        // $fixer = new Fixer(['Ellipsis', 'Dimension', 'Unit', 'Dash', 'SmartQuotes', 'FrenchNoBreakSpace', 'NoSpaceBeforeComma', 'CurlyQuote', 'Trademark']);
        // $fixer->setLocale($this->app->get('locale'));

        // $presets = [
        //     'default' => $fixer,
        // ];
        // $twig->addExtension(new JoliTypoExtension($presets));

        return $twig;
    }
}
