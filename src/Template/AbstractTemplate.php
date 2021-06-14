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

    protected static function isCurrentTemplate(): bool
    {
        if (!\is_singular()) {
            return false;
        }

        return static::getTemplate() . '.php' === \get_page_template_slug();
    }
}
