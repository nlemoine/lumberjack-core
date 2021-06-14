<?php

namespace Rareloop\Lumberjack\Fields;

use StoutLogic\AcfBuilder\FieldsBuilder as AcfBuilderFieldsBuilder;

class FieldsBuilder extends AcfBuilderFieldsBuilder
{
    public function __toString()
    {
        $config = $this->build();
        unset($config['location']);
        return \json_encode($config);
    }
}
