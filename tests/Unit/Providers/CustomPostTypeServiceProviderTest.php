<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Models\Post;
use Rareloop\Lumberjack\Providers\CustomPostTypeServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class CustomPostTypeServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    // public function testShouldCallRegisterPostTypeForEachConfiguredPostType()
    // {
    //     $app = new Application(__DIR__ . '/..');

    //     $config = new Config();

    //     $config->set('posttypes.register', [
    //         CustomPost1::class,
    //         CustomPost2::class,
    //     ]);

    //     Functions\expect('register_extended_post_type')
    //         ->times(2);

    //     $provider = new CustomPostTypeServiceProvider($app);
    //     $provider->boot($config);
    // }
}

class CustomPost1 extends Post
{
    public static function getPostType(): string
    {
        return 'custom_post_1';
    }

    protected static function getPostTypeConfig(): array
    {
        return [
            'not' => 'empty',
        ];
    }
}

class CustomPost2 extends Post
{
    public static function getPostType(): string
    {
        return 'custom_post_1';
    }

    protected static function getPostTypeConfig(): array
    {
        return [
            'not' => 'empty',
        ];
    }
}
