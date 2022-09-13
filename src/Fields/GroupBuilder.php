<?php

namespace Rareloop\Lumberjack\Fields;

use StoutLogic\AcfBuilder\GroupBuilder as AcfGroupBuilder;

class GroupBuilder extends AcfGroupBuilder
{
    public function __construct($name, $type = 'group', $config = [])
    {
        parent::__construct($name, $type, $config);
        $this->fieldsBuilder = new FieldsBuilder($name);
        $this->fieldsBuilder->setParentContext($this);
    }
}
