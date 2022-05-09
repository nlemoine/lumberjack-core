<?php

namespace Rareloop\Lumberjack\Fields;

use StoutLogic\AcfBuilder\FieldBuilder;
use StoutLogic\AcfBuilder\FieldsBuilder as AcfBuilderFieldsBuilder;

class FieldsBuilder extends AcfBuilderFieldsBuilder
{
    public function __toString()
    {
        $config = $this->build();
        unset($config['location']);
        return \json_encode($config, JSON_THROW_ON_ERROR);
    }

    public function unshiftFields($fields)
    {
        return $this->insertFields($fields, 0);
    }

    public function pushField(FieldBuilder $field)
    {
        return $this->getFieldManager()->pushField($field);
    }

    public function insertField(FieldBuilder $field, int $index)
    {
        return $this->getFieldManager()->insertFields($field, $index);
    }

    public function insertFields($fields, int $index)
    {
        if ($fields instanceof AcfBuilderFieldsBuilder) {
            $builder = clone $fields;
            $fields = $builder->getFields();
        }

        foreach ($fields as $field) {
            $this->getFieldManager()->insertFields($field, $index);
        }

        return $this;
    }

    public function getFieldIndex(string $name): int
    {
        return $this->getFieldManager()->getFieldIndex($name);
    }
}
