<?php

namespace Rareloop\Lumberjack\Template;

use Rareloop\Lumberjack\Models\Page;

abstract class AbstractTemplate
{
    abstract public static function getTemplate(): string;

    abstract public static function getTemplateName(): string;

    /**
     * @return array<string>
     */
    public static function getPostTypes(): array
    {
        return [Page::getPostType()];
    }

    protected static function isCacheable(): bool
    {
        return true;
    }
}
