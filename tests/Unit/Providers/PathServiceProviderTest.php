<?php

namespace Rareloop\Lumberjack\Test\Providers;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Http\Lumberjack;
use Rareloop\Lumberjack\Providers\PathServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class PathServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testThemePathAndUrlAreSet()
    {
        $themePath = '/abspath/wp-content/themes/lumberjack/';
        $themeUrl = 'https://example.com/wp-content/themes/lumberjack/';

        Functions\when('is_admin')->justReturn(false);

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        Functions\expect('get_template_directory')
            ->once()
            ->andReturn($themePath);

        Functions\expect('get_template_directory_uri')
            ->once()
            ->andReturn($themeUrl);

        $app->register(new PathServiceProvider($app));
        $lumberjack->bootstrap();

        // Make sure it's called once
        $app->get('path.theme');
        $app->get('url.theme');
        $this->assertSame(\rtrim($themePath, '/'), $app->get('path.theme'));
        $this->assertSame(\rtrim($themeUrl, '/'), $app->get('url.theme'));
    }

    public function testUploadsPathAndUrlAreSet()
    {
        $uploads = [
            'basedir' => '/abspath/wp-content/uploads/',
            'baseurl' => 'https://example.com/wp-content/uploads/',
        ];

        Functions\when('is_admin')->justReturn(false);

        $app = new Application(__DIR__ . '/../');
        $lumberjack = new Lumberjack($app);

        Functions\expect('wp_get_upload_dir')
            ->times(2)
            ->andReturn($uploads);

        $app->register(new PathServiceProvider($app));
        $lumberjack->bootstrap();

        // Make sure it's called once
        $app->get('path.uploads');
        $app->get('url.uploads');
        $this->assertSame(\rtrim($uploads['basedir'], '/'), $app->get('path.uploads'));
        $this->assertSame(\rtrim($uploads['baseurl'], '/'), $app->get('url.uploads'));
    }
}
