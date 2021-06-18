<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Providers\ThemeServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class ThemeServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testShouldCallAddThemeSupportForKeyInConfig()
    {
        $app = new Application(__DIR__ . '/..');
        $config = new Config();

        $config->set('theme.support', [
            'post-thumbnail',
        ]);
        $app->bind(Config::class, $config);

        Functions\expect('add_theme_support')
            ->with('post-thumbnail')
            ->once();

        $provider = new ThemeServiceProvider($app);
        $provider->addThemeSupport($config);
    }

    public function testShouldCallAddThemeSupportForKeyValueInConfig()
    {
        $app = new Application(__DIR__ . '/..');

        $config = new Config();

        $config->set('theme.support', [
            'post-formats' => ['aside', 'gallery'],
        ]);
        $app->bind(Config::class, $config);

        Functions\expect('add_theme_support')
            ->with('post-formats', ['aside', 'gallery'])
            ->once();

        $provider = new ThemeServiceProvider($app);
        $provider->addThemeSupport($config);
    }
}
