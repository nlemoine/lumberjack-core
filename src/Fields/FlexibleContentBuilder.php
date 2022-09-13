<?php

namespace Rareloop\Lumberjack\Fields;

use StoutLogic\AcfBuilder\FlexibleContentBuilder as AcfFlexibleContentBuilder;

class FlexibleContentBuilder extends AcfFlexibleContentBuilder
{
    /**
     * Add a layout, which is a FieldsBuilder. `addLayout` can be chained to add
     * multiple layouts to the Flexible Content field.
     * @param string|FieldsBuilder $layout layout name.
     * Alternatively supply a FieldsBuilder to reuse existing fields. The name
     * will be inferred from the FieldsBuilder's name.
     * @param array $args filed configuration
     * @return FieldsBuilder
     */
    public function addLayout($layout, $args = [])
    {
        if ($layout instanceof FieldsBuilder) {
            $layout = clone $layout;
        } else {
            $layout = new FieldsBuilder($layout, $args);
        }
        return parent::addLayout($layout, $args);
    }
}
