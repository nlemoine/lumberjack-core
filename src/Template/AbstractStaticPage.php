<?php

namespace Rareloop\Lumberjack\Template;

abstract class AbstractStaticPage
{
    abstract public static function getPageType(): string;
}
