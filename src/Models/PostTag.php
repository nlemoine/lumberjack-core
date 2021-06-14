<?php

namespace Rareloop\Lumberjack\Models;

class PostTag extends AbstractTerm
{
    public static function getTaxonomy(): string
    {
        return 'post_tag';
    }

    public static function getTaxonomyObjectTypes(): array
    {
        return [
            Post::getPostType(),
        ];
    }
}
