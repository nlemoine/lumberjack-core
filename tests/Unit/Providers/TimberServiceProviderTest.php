<?php

namespace Rareloop\Lumberjack\Test\Providers;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Bootstrappers\BootProviders;
use Rareloop\Lumberjack\Bootstrappers\RegisterProviders;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Http\Lumberjack;
use Rareloop\Lumberjack\Providers\TimberServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Timber\Timber;
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;

class TimberServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testTimberPluginIsInitialiased()
    {
        Functions\expect('is_admin')->andReturn(false);

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        $app->register(new TimberServiceProvider($app));
        $lumberjack->bootstrap();

        $this->assertTrue($app->has('timber'));
        $this->assertSame($app->get('timber'), $app->get(Timber::class));
    }

    public function testDirnameVariableIsSetFromConfig()
    {
        Functions\expect('is_admin')->andReturn(false);

        $app = new Application(__DIR__ . '/../');

        $config = new Config();
        $config->set('timber.paths', [
            'path/one',
            'path/two',
            'path/three',
        ]);

        $app->bind('config', $config);
        $app->bind(Config::class, $config);

        $app->bootstrapWith([
            RegisterProviders::class,
            BootProviders::class,
        ]);

        $app->register(new TimberServiceProvider($app));

        $this->assertTrue($app->has('timber'));
        $this->assertSame([
            'path/one',
            'path/two',
            'path/three',
        ], $app->get('timber')::$dirname);
    }

    public function testLoaderFilter()
    {
        Functions\when('is_admin')->justReturn(false);
        Functions\when('get_stylesheet_directory')->justReturn('');
        Functions\when('get_template_directory')->justReturn('');

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new TimberServiceProvider($app);
        $app->register($provider);
        $lumberjack->bootstrap();

        $this->assertSame(10, \has_filter('timber/loader/loader', [$provider, 'addSymfonyFormThemePath']));
    }

    public function testAddSymfonyFormTheme()
    {
        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new TimberServiceProvider($app);

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../');
        $loader = $provider->addSymfonyFormThemePath($loader);
        $this->assertStringContainsString('symfony/twig-bridge/Resources/views/Form', $loader->getPaths()[1]);
    }

    public function testCachePathFilter()
    {
        Functions\when('is_admin')->justReturn(false);

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new TimberServiceProvider($app);
        $app->register($provider);
        $lumberjack->bootstrap();
        $this->assertSame(10, \has_filter('timber/cache/location', [$provider, 'setCacheLocation']));
    }

    public function testTwigFilter()
    {
        Functions\when('is_admin')->justReturn(false);

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);
        $provider = new TimberServiceProvider($app);
        $app->register($provider);
        $lumberjack->bootstrap();

        $this->assertSame(10, \has_filter('timber/loader/twig', [$provider, 'addTwigExtensions']));
    }

    public function testTwigExtensions()
    {
        $app = new Application(__DIR__ . '/../');
        $provider = new TimberServiceProvider($app);

        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../');
        $twig = new Environment($loader);

        $twig = $provider->addTwigExtensions($twig);

        $this->assertTrue($twig->getExtension(HtmlExtension::class) instanceof HtmlExtension);
    }
}
