<?php

namespace Rareloop\Lumberjack\Models;

class Category extends AbstractTerm
{
    public static function getTaxonomy(): string
    {
        return 'category';
    }

    public static function getTaxonomyObjectTypes(): array
    {
        return [
            Post::getPostType(),
        ];
    }
}
