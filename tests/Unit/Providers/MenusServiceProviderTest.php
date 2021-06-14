<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Providers\MenusServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class MenusServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testSingleMenuShouldBeSetFromConfig()
    {
        $app = new Application(__DIR__ . '/..');
        $config = new Config();

        $config->set('menus.menus', [
            [
                'menu-name' => 'Menu Name',
            ],
        ]);

        Functions\expect('register_nav_menus')
            ->once()
            ->with([[
                'menu-name' => 'Menu Name',
            ]]);

        $provider = new MenusServiceProvider($app);
        $provider->registerNavMenus($config);
    }

    public function testMultipleMenusShouldBeSetFromConfig()
    {
        $app = new Application(__DIR__ . '/..');
        $config = new Config();

        $config->set('menus.menus', [
            [
                'menu-name' => 'Menu Name',
            ],
            [
                'another-menu-name' => 'Another Menu Name',
            ],
        ]);

        Functions\expect('register_nav_menus')
            ->once()
            ->with([[
                'menu-name' => 'Menu Name',
            ], [
                'another-menu-name' => 'Another Menu Name',
            ]]);

        $provider = new MenusServiceProvider($app);
        $provider->registerNavMenus($config);
    }
}
