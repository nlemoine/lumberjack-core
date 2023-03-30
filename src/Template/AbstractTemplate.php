<?php

namespace Rareloop\Lumberjack\Template;

use Rareloop\Lumberjack\Models\Page;

abstract class AbstractTemplate
{
    abstract public static function getTemplate(): string;

    abstract public static function getTemplateName(): string;

    public static function getTemplateFilename(): string
    {
        return static::getTemplate() . '.php';
    }

    /**
     * @return array<string>
     */
    public static function getPostTypes(): array
    {
        return [Page::getPostType()];
    }

    public static function isCacheable(): bool
    {
        return true;
    }
}
