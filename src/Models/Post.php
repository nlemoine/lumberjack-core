<?php

namespace Rareloop\Lumberjack\Models;

class Post extends AbstractPostType
{
    public static function getPostType(): string
    {
        return 'post';
    }
}
