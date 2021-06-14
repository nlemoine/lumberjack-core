<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Providers\OembedServiceProvider;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;
use Timber\Timber;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class OembedServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testFiltersAreAdded()
    {
        $app = new Application(__DIR__ . '/..');

        $provider = new OembedServiceProvider($app);
        $provider->boot();

        $this->assertSame(10, \has_filter('oembed_dataparse', [$provider, 'saveOembedData']));
        $this->assertSame(10, \has_filter('embed_oembed_html', [$provider, 'wrapEmbed']));
        $this->assertSame(10, \has_filter('oembed_providers', [$provider, 'filterProviders']));
    }

    public function testOembedDataSaving()
    {
        $app = new Application(__DIR__ . '/..');

        $provider = new OembedServiceProvider($app);
        $html = '<embed>';
        $data = [
            'width'  => 100,
            'height' => 100,
            'url'    => 'https://embedurl',
        ];
        $url = 'https://youtube/?v=123456';

        $post = new \stdClass();
        $post->ID = 1;
        $key = '_oembed_data_' . \md5($url);

        Functions\expect('get_post')->once()->andReturn($post);
        Functions\expect('update_post_meta')->once()->with($post->ID, $key, $data);

        $return_html = $provider->saveOembedData($html, $data, $url);
        $this->assertSame($html, $return_html);
    }

    public function testWrapEmbedWithNoExistingTemplates()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application(__DIR__ . '/..');
        $logger = Mockery::mock(LoggerInterface::class);
        $app->bind(LoggerInterface::class, $logger);
        $provider = new OembedServiceProvider($app);

        $post_id = 1;
        $html = '<embed>';
        $url = 'https://youtube/?v=123456';
        $key = '_oembed_data_' . \md5($url);
        $data = [
            'url'           => $url,
            'type'          => 'video',
            'provider_name' => 'YouTube',
        ];

        Functions\expect('get_post_meta')
            ->once()
            ->with($post_id, $key, true)
            ->andReturn($data)
        ;

        $logger->shouldReceive('error')->once();

        $return_html = $provider->wrapEmbed($html, $url, [], $post_id);
        $this->assertSame($html, $return_html);
    }

    public function testWrapEmbed()
    {
        Functions\expect('is_admin')->once()->andReturn(false);

        $app = new Application(__DIR__ . '/..');
        $timber = Mockery::mock('alias:' . Timber::class);
        $app->bind(Timber::class, $timber);
        $provider = new OembedServiceProvider($app);

        $post_id = 1;
        $html = '<embed>';
        $url = 'https://youtube/?v=123456';
        $key = '_oembed_data_' . \md5($url);
        $embed_data = [
            'url'           => $url,
            'type'          => 'video',
            'provider_name' => 'YouTube',
        ];

        Functions\expect('get_post_meta')
            ->once()
            ->with($post_id, $key, true)
            ->andReturn($embed_data)
        ;

        $timber
            ->shouldReceive('fetch')
            ->once()
            ->andReturn('<new embed>')
        ;

        $return_html = $provider->wrapEmbed($html, $url, [], $post_id);
        $this->assertSame('<new embed>', $return_html);
    }
}
