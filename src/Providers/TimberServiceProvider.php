<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Config;
use Timber\Loader;
use Timber\Timber;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;
use Twig\Loader\LoaderInterface;

class TimberServiceProvider extends ServiceProvider
{
    public function register()
    {
        $timber = new Timber();

        $this->app->singleton('timber', $timber);
        $this->app->singleton(Timber::class, $timber);

        $this->app->singleton('twig', function () {
            return (new Loader())->get_twig();
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
    }

    /**
     * Add Symfony form theme path
     *
     * @param LoaderInterface $loader
     */
    public function addSymfonyFormThemePath($loader): LoaderInterface
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
        return $this->app->get('path.project') . '/var/cache/twig';
    }

    /**
     * Add Twig extensions
     */
    public function addTwigExtensions(Environment $twig): Environment
    {
        // $twig->addExtension(new AssetExtension($this->app->get('assets.packages')));
        // $twig->addExtension(new SvgHelpersExtension($this->app->get('assets.packages')->getPackage('path')));
        $twig->addExtension(new HtmlExtension());
        // $twig->addExtension(
        //     new ImageFactoryExtension($this->app->get('image.factory'))
        // );

        // $twig->addExtension(new RoutingExtension($this->app->get('router.generator')));
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
