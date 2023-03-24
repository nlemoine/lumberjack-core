<?php

namespace Rareloop\Lumberjack\Models;

abstract class AbstractPostTemplate extends AbstractPost
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

    public static function getTemplateFilename(): string
    {
        return static::getTemplate() . '.php';
    }

    public static function isCacheable(): bool
    {
        return true;
    }
}
