<?php

namespace Rareloop\Lumberjack\Fields;

use StoutLogic\AcfBuilder\FieldsBuilder as AcfBuilderFieldsBuilder;

class FieldsBuilder extends AcfBuilderFieldsBuilder
{
    public function __toString()
    {
        $config = $this->build();
        unset($config['location']);
        return \json_encode($config, JSON_THROW_ON_ERROR);
    }

    public function insertFields($fields, int $index)
    {
        if ($fields instanceof FieldsBuilder) {
            $builder = clone $fields;
            $fields = $builder->getFields();
        }

        foreach ($fields as $field) {
            $this->getFieldManager()->insertFields($field, $index);
        }

        return $this;
    }
}
