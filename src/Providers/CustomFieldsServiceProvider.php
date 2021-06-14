<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Admin\Option\AbstractAcfOptionPage;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Contracts\HasAcfFields;
use Rareloop\Lumberjack\Fields\FieldsBuilder;
use Rareloop\Lumberjack\Models\AbstractPostType;
use Rareloop\Lumberjack\Models\AbstractTerm;
use Rareloop\Lumberjack\Template\AbstractTemplate;
use Rareloop\Lumberjack\Template\FrontPage;

class CustomFieldsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!\function_exists('acf_add_local_field_group')) {
            return;
        }
        \add_action('acf/init', [$this, 'registerFields']);
    }

    /**
     * Register ACF fields
     */
    public function registerFields(): array
    {
        $groups = $this->getRegisteredFields();

        foreach ($groups as $fields) {
            \acf_add_local_field_group($fields->build());
        }

        return $groups;
    }

    private function getRegisteredFields(): array
    {
        $classes = $this->getClasses();

        $groups = [];
        foreach ($classes as $class) {
            $fields = $class::getCustomFields();
            if (!\is_array($fields)) {
                $fields = [$fields];
            }

            $fields = \array_filter($fields, function ($fields) {
                return $fields instanceof FieldsBuilder;
            });

            $order = [];
            foreach ($fields as $f) {
                $field_hash = \md5((string) $f);

                $group_position = $f->getGroupConfig('position') ?? 'default';
                $order[$group_position] = isset($order[$group_position]) ? $order[$group_position] + 10 : 0;
                $f->setGroupConfig('menu_order', $order[$group_position]);

                $location = $this->getFieldsLocation($class);

                // Remove duplicate field with different locations
                // and merge locations
                if (\in_array($field_hash, \array_keys($groups), true)) {
                    $field_location = $groups[$field_hash]->getLocation();
                    if ($location) {
                        $field_location->or(...$location);
                    }
                    continue;
                }
                $f->setLocation(...$location);
                $this->configureFields($f);
                $groups[$field_hash] = $f;
            }
        }

        return \array_values($groups);
    }

    /**
     * Get classes that can have custom fields
     */
    private function getClasses(): array
    {
        $config = $this->app->get(Config::class);
        $classes = \array_merge(
            (array) $config->get('posttypes.register', []),
            (array) $config->get('taxonomies.register', []),
            (array) $config->get('admin-pages', []),
            (array) $config->get('entities', []),
            (array) $config->get('templates', []),
            (array) $config->get('fields', []),
        );

        return \array_values(\array_filter($classes, [$this, 'filterFieldsAwareClasses']));
    }

    /**
     * Filter objects that implements HasAcfFields interface
     *
     * @return boolean
     */
    private function filterFieldsAwareClasses(string $class): bool
    {
        return \in_array(HasAcfFields::class, (array) \class_implements($class), true);
    }

    /**
     * Configure fields
     */
    private function configureFields(FieldsBuilder $fields): FieldsBuilder
    {
        $fields->setGroupConfig('instruction_placement', 'field');
        return $fields;
    }

    /**
     * Set fields location
     *
     * @param string $class
     */
    private function getFieldsLocation($class): ?array
    {
        switch (true) {
            // post type
            case \is_subclass_of($class, AbstractPostType::class):
                return ['post_type', '==', $class::getPostType()];
                break;

            // template
            case \is_subclass_of($class, AbstractTemplate::class):
                return ['page_template', '==', \sprintf('%s.php', $class::getTemplate())];
                break;

            // static template
            case \is_subclass_of($class, FrontPage::class):
                return ['page_type', '==', 'front_page'];
                break;

            // taxonomy
            case \is_subclass_of($class, AbstractTerm::class):
                return ['taxonomy', '==', $class::getTaxonomy()];
                break;

            // option
            case \is_subclass_of($class, AbstractAcfOptionPage::class):
                return ['options_page', '==', $class::getPageSlug()];
                break;
        }

        return null;
    }

    /**
     * Merge fields locations
     *
     * @param [type] $fields
     */
    private function mergeFieldsLocations(FieldsBuilder $fields, array $condition)
    {
        $location = $fields->getLocation();
        if (!$location) {
            $fields->setLocation(...$condition);
            return $fields;
        }

        $location->orCondition(...$condition);
        return $fields;
    }

    /**
     * Localize fields
     */
    private function localizeFields(FieldsBuilder $fields): FieldsBuilder
    {
        // Return fields if Polylang isn't there
        if (!\function_exists('pll_the_languages')) {
            return $fields;
        }

        // Avoid expensive queries on front end
        if (\is_admin()) {
            $languages = \PLL()->model->get_languages_list();
        } else {
            $languages = \pll_languages_list();
            $languages = \array_map(function ($lang) {
                $obj = new \stdClass();
                $obj->slug = $lang;

                return $obj;
            }, $languages);
        }

        // No languages
        if (empty($languages)) {
            return $fields;
        }

        // Create a new field set & filter i18n fields
        $fields_i18n = new FieldsBuilder('');
        foreach ($fields->getFields() as $field) {
            $acf_field_config = $field->build();
            if (isset($acf_field_config['i18n']) && $acf_field_config['i18n']) {
                $fields_i18n->addFields([$field]);
            }
        }

        // No i18n fields
        if (empty($fields_i18n->getFields())) {
            return $fields;
        }

        // Store original fields keys
        $keys = \array_map(function ($field) {
            return $field->getName();
        }, $fields_i18n->getFields());

        $current_lang = \pll_current_language();

        // Add fields for each lang
        foreach ($languages as $lang) {
            // Only register the field for the current lang field on front end
            if (!\is_admin() && $current_lang !== $lang->slug) {
                continue;
            }

            // Only add tabs when on admin
            if (\is_admin()) {
                $fields
                    ->addTab($lang->slug)
                    ->setLabel(\sprintf('%s %s', $lang->flag, $lang->name))
                ;
            }

            foreach ($fields_i18n->getFields() as $field) {
                // Modify field name
                $new_field = clone $field;
                $new_field->setConfig('name', \sprintf('%s_%s', $field->getName(), $lang->slug));
                $new_field->setKey($new_field->getName());

                // Create a new builder
                $new_builder = new FieldsBuilder('');
                $new_builder->addFields([$new_field]);
                $fields->addFields($new_builder);
            }
        }

        // Remove original fields
        foreach ($keys as $key) {
            $fields->removeField($key);
        }

        return $fields;
    }
}
