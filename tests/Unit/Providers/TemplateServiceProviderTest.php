<?php

namespace Rareloop\Lumberjack\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Providers\TemplateServiceProvider;
use Rareloop\Lumberjack\Template\AbstractTemplate;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class TemplateServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testFiltersAreAdded()
    {
        $app = new Application(__DIR__ . '/..');

        $config = new Config();
        $config->set('templates', [
            CustomTemplate::class,
            ContactTemplate::class,
        ]);
        $app->bind(Config::class, $config);

        $provider = new TemplateServiceProvider($app);
        $provider->boot();

        $this->assertSame(10, \has_filter('theme_templates', [$provider, 'registerTemplates']));
        $this->assertSame(10, \has_action('template_redirect', [$provider, 'setTemplateCachePolicy']));
    }

    public function testRegisterTemplates()
    {
        $app = new Application(__DIR__ . '/..');

        $config = new Config();
        $config->set('templates', [
            CustomTemplate::class,
            ContactTemplate::class,
        ]);
        $app->bind(Config::class, $config);

        $provider = new TemplateServiceProvider($app);
        $templates_for_page = $provider->registerTemplates([], Mockery::mock('WP_Theme'), Mockery::mock('WP_Post'), 'page');
        $templates_for_post = $provider->registerTemplates([], Mockery::mock('WP_Theme'), Mockery::mock('WP_Post'), 'post');

        $this->assertSame([
            'contact-page.php' => 'Contact page',
        ], $templates_for_page);
        $this->assertSame([
            'custom-template.php' => 'My Custom Template',
        ], $templates_for_post);
    }
}

class CustomTemplate extends AbstractTemplate
{
    public static function getTemplate(): string
    {
        return 'custom-template';
    }

    public static function getTemplateName(): string
    {
        return 'My Custom Template';
    }

    public static function getPostTypes()
    {
        return ['post'];
    }
}

class ContactTemplate extends AbstractTemplate
{
    public static function getTemplate(): string
    {
        return 'contact-page';
    }

    public static function getTemplateName(): string
    {
        return 'Contact page';
    }
}
