<?php

namespace Rareloop\Lumberjack\Test;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Application;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Contracts\HasAcfFields;
use Rareloop\Lumberjack\Fields\FieldsBuilder;
use Rareloop\Lumberjack\Models\Category;
use Rareloop\Lumberjack\Models\Post;
use Rareloop\Lumberjack\Providers\CustomFieldsServiceProvider;
use Rareloop\Lumberjack\Template\AbstractTemplate;
use Rareloop\Lumberjack\Test\Unit\BrainMonkeyPHPUnitIntegration;

class CustomFieldsServiceProviderTest extends TestCase
{
    use BrainMonkeyPHPUnitIntegration;

    public function testHooksAreAdded()
    {
        Functions\when('acf_add_local_field_group')->justReturn('');

        $app = new Application(__DIR__ . '/..');

        $provider = new CustomFieldsServiceProvider($app);
        $provider->boot();

        $this->assertSame(10, \has_action('acf/init', [$provider, 'registerFields']));
    }

    public function testFieldsAreRegistered()
    {
        Functions\when('acf_add_local_field_group')->justReturn('');

        $app = new Application(__DIR__ . '/..');

        $config = new Config();
        $config->set('posttypes.register', [
            CustomPost::class,
        ]);
        $config->set('taxonomies.register', [
            CustomTax::class,
        ]);
        $config->set('templates', [
            DummyTemplate::class,
        ]);
        $app->bind(Config::class, $config);

        $provider = new CustomFieldsServiceProvider($app);
        $groups = $provider->registerFields();

        $this->assertSame(3, \count($groups));
    }

    public function testDuplicateFieldsAreMerged()
    {
        Functions\when('acf_add_local_field_group')->justReturn('');

        $app = new Application(__DIR__ . '/..');

        CustomPost::$fields = $this->getFields();
        CustomTax::$fields = $this->getFields();

        $config = new Config();
        $config->set('posttypes.register', [
            CustomPost::class,
        ]);
        $config->set('taxonomies.register', [
            CustomTax::class,
        ]);

        $app->bind(Config::class, $config);

        $provider = new CustomFieldsServiceProvider($app);
        $groups = $provider->registerFields();

        $expected_location = [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post',
                ],
            ],
            [
                [
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => 'category',
                ],
            ],
        ];

        $this->assertSame(1, \count($groups));
        $this->assertSame($expected_location, $groups[0]->build()['location']);
    }

    private function getFields($key = 'some_key')
    {
        $fields = new FieldsBuilder($key);
        $fields
            ->addText('title')
        ;
        return $fields;
    }
}

class CustomPost extends Post implements HasAcfFields
{
    public static $fields;

    public static function getCustomFields()
    {
        if (self::$fields) {
            return self::$fields;
        }

        $fields = new FieldsBuilder('post_key');
        $fields
            ->addText('title')
        ;
        return $fields;
    }
}
class CustomTax extends Category implements HasAcfFields
{
    public static $fields;

    public static function getCustomFields()
    {
        if (self::$fields) {
            return self::$fields;
        }

        $fields = new FieldsBuilder('tax_key');
        $fields
            ->addText('title')
        ;
        return $fields;
    }
}
class DummyTemplate extends AbstractTemplate implements HasAcfFields
{
    public static function getCustomFields()
    {
        $fields = new FieldsBuilder('template_key');
        $fields
            ->addText('title')
        ;
        return $fields;
    }

    public static function getTemplate(): string
    {
        return 'dummy';
    }

    public static function getTemplateName(): string
    {
        return 'dummy';
    }
}
