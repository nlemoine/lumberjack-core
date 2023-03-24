<?php

namespace Rareloop\Lumberjack\Models;

abstract class AbstractStaticPageType extends AbstractPost
{
    abstract public static function getPageType(): string;
}
