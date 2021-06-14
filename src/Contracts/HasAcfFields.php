<?php

namespace Rareloop\Lumberjack\Contracts;

use StoutLogic\AcfBuilder\FieldsBuilder;

interface HasAcfFields
{
    /**
     * Get custom fields.
     *
     * @return array|FieldsBuilder
     */
    public static function getCustomFields();
}
