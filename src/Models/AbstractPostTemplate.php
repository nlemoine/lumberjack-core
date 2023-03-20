<?php

namespace Rareloop\Lumberjack\Models;

abstract class AbstractPostTemplate extends AbstractPost
{
    abstract public static function getTemplateName(): string;

    abstract public static function getTemplateLabel(): string;

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
