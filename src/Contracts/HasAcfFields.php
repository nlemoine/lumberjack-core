<?php

namespace Rareloop\Lumberjack\Contracts;

use StoutLogic\AcfBuilder\FieldsBuilder;

interface HasAcfFields
{
    /**
     * @return array|FieldsBuilder
     */
    public static function getCustomFields();
}
