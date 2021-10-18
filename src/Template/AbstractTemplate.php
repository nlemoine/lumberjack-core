<?php

namespace Rareloop\Lumberjack\Template;

use Rareloop\Lumberjack\Models\Page;

abstract class AbstractTemplate
{
    abstract public static function getTemplate(): string;

    abstract public static function getTemplateName(): string;

    public static function getPostTypes()
    {
        return [Page::getPostType()];
    }

    protected static function isCacheable(): bool
    {
        return true;
    }
}
