<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Admin\Option\AbstractAcfOptionPage;
use Rareloop\Lumberjack\Blocks\AbstractAcfBlock;
use Rareloop\Lumberjack\Config;
use Rareloop\Lumberjack\Contracts\HasAcfFields;
use Rareloop\Lumberjack\Fields\FieldsBuilder;
use Rareloop\Lumberjack\Models\AbstractPostTemplate;
use Rareloop\Lumberjack\Models\AbstractPostType;
use Rareloop\Lumberjack\Models\AbstractTerm;
use Rareloop\Lumberjack\Models\Attachment;
use Rareloop\Lumberjack\Models\NavMenuItem;
use Rareloop\Lumberjack\Models\User;
use Rareloop\Lumberjack\Template\AbstractTemplate;
use Rareloop\Lumberjack\Template\FrontPage;
use StoutLogic\AcfBuilder\TabBuilder;

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

    /**
     * @return FieldsBuilder[]
     */
    private function getRegisteredFields(): array
    {
        $classes = $this->getClasses();

        $isMultiLanguage = \function_exists('PLL');

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

                // TODO: merge location
                $location = $this->getFieldsLocation($class);
                $locationConfig = $location;
                if (!$location && !$f->getLocation()) {
                    continue;
                }

                // Remove duplicate field with different locations
                // and merge locations
                if (\in_array($field_hash, \array_keys($groups), true)) {
                    $field_location = $groups[$field_hash]->getLocation();
                    $field_location->or(...$location);
                    continue;
                }

                // Field has a location
                if (!$f->getLocation()) {
                    $f->setLocation(...$location);
                }

                // Localize options fields if they are translatable and Polylang is active
                if ($isMultiLanguage && isset($locationConfig[0]) && $locationConfig[0] === 'options_page') {
                    $f = $this->localizeFields($f);
                }

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
            (array) $config->get('templates', []),
            (array) $config->get('fields', []),
            (array) $config->get('blocks', []),
            (array) $config->get('menus.menu_item_classes', []),
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
     * @param string|object $class
     */
    private function getFieldsLocation($class): ?array
    {
        switch (true) {
            // attachment
            case \is_subclass_of($class, Attachment::class):
                return ['attachment', '==', 'all'];
                break;

                // post type
            case \is_subclass_of($class, AbstractPostType::class):
                return ['post_type', '==', $class::getPostType()];
                break;

                // template
            case \is_subclass_of($class, AbstractTemplate::class) || \is_subclass_of($class, AbstractPostTemplate::class):
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

                // nav menu item
            case \is_subclass_of($class, NavMenuItem::class):
                if (\method_exists($class, 'getCustomFieldsLocation')) {
                    return ['nav_menu_item', '==', $class::getCustomFieldsLocation()];
                }
                return ['nav_menu_item', '==', 'all'];
                break;

                // option
            case \is_subclass_of($class, AbstractAcfOptionPage::class):
                return ['options_page', '==', $class::getPageSlug()];
                break;

                // block
            case \is_subclass_of($class, AbstractAcfBlock::class):
                return ['block', '==', 'acf/' . $class::getName()];
                break;

                // user
            case \is_subclass_of($class, User::class):
                return ['user_form', '==', 'all'];
                break;
        }

        return null;
    }

    // /**
    //  * Merge fields locations
    //  *
    //  * @param [type] $builder
    //  */
    // private function mergeFieldsLocations(FieldsBuilder $fields, array $condition)
    // {
    //     $location = $fields->getLocation();
    //     if (!$location) {
    //         $fields->setLocation(...$condition);
    //         return $fields;
    //     }

    //     $location->orCondition(...$condition);
    //     return $fields;
    // }

    /**
     * Localize fields
     */
    private function localizeFields(FieldsBuilder $builder): FieldsBuilder
    {
        // Return fields if Polylang isn't there
        if (!\function_exists('pll_the_languages')) {
            return $builder;
        }

        // return $builder;
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
            return $builder;
        }

        $fields = $builder->getFields();

        // Get fields needing translation
        $fields_needing_translations = \array_filter($fields, function ($field) {
            return $field->getConfig()['translate'] ?? false;
        });

        // No fields to translate
        if (empty($fields_needing_translations)) {
            return $builder;
        }

        // Get current & default language
        $current_lang = \pll_current_language();
        $default_language = \pll_default_language();

        $remove = [];

        foreach ($fields as $k => $field) {
            $needs_translation = $field->getConfig()['translate'] ?? false;
            if (!$needs_translation) {
                continue;
            }

            // $next_field_is_translatable = isset($fields[$k + 1]) ? $fields[$k + 1]->getConfig()['translate'] ?? false : false;
            // $previous_field_is_translatable = isset($fields[$k - 1]) ? $fields[$k - 1]->getConfig()['translate'] ?? false : false;

            $field_name = $field->getName();
            $field_index = $builder->getFieldIndex($field_name);
            $field_label = $field->getConfig()['label'] ?? null;

            foreach ($languages as $lang) {
                if (\is_admin()) {
                    $label = \sprintf('<img src="%s" /> %s', $lang->flag_url, $field_label);
                    if ($default_language === $lang->slug) {
                        // !$previous_field_is_translatable && $builder->insertField($tab, $field_index);
                        $field->setLabel($label);
                    }

                    //     $tab_key = sprintf('tab_%s_%s', $lang->slug, $k);
                    //     $tab = new TabBuilder($tab_key, 'tab', [
                    //         'label' => $lang->name,
                    //     ]);

                    //     $tab_break = new TabBuilder(sprintf('tab_break_%s', $k), 'tab', [
                    //         'label' => '',
                    //         'endpoint' => true,
                    //     ]);
                }

                // // Clone & create new field for each language
                if (
                    (\is_admin() && $default_language !== $lang->slug)
                    || (!\is_admin() && $current_lang === $lang->slug && $current_lang !== $default_language)
                ) {
                    $translated_key = $default_language === $lang->slug ? $field_name : \sprintf('%s_%s', $field_name, $lang->slug);
                    $translated_field = clone $field;
                    $translated_field->setConfig('name', $translated_key);
                    $translated_field->setKey($translated_field->getName());
                    $translated_field->setParentContext($builder);
                    if (\is_admin()) {
                        $translated_field->setLabel($label);
                    }
                    $field_index = $builder->getFieldIndex($field_name);
                    $builder->insertField($translated_field, $field_index + 1);
                    // if (is_admin()) {
                    //     $translated_index = $builder->getFieldIndex($translated_key);
                    //     $tab->endpoint();
                    //     !$previous_field_is_translatable && $builder->insertField($tab, $translated_index);
                    //     $tab->removeEndpoint();
                    // }
                }

                // if(is_admin() && !$next_field_is_translatable && $default_language !== $lang->slug) {
                //     // $builder->insertField($tab_break, $translated_index + 2);
                // }

                // Only register the field for the current lang field on front end
                if (!\is_admin() && $current_lang !== $default_language && $current_lang !== $lang->slug) {
                    $remove[] = $field_name;
                }
            }
        }

        return $builder;
    }
}
