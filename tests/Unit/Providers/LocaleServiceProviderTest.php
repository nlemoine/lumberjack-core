<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Providers\LocaleServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class LocaleServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testLocale()
    {
        $locale = 'fr_FR';
        Functions\expect('get_locale')->times(2)->andReturn($locale);

        $app = new Application(__DIR__ . '/../');
        $provider = new LocaleServiceProvider($app);
        $app->register($provider);

        $this->assertSame($locale, $app->get('locale'));
        $this->assertSame('fr', $app->get('locale.short'));
    }

    public function testPhpLocaleIsSet()
    {
        Functions\when('get_locale')->justReturn('fr_FR');

        $app = new Application(__DIR__ . '/../');
        $provider = new LocaleServiceProvider($app);
        $provider->register();
        $provider->boot();

        $this->assertSame('fr-FR', \locale_get_default());
    }
}
