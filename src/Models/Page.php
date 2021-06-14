<?php

namespace Rareloop\Lumberjack\Models;

class Page extends AbstractPostType
{
    /**
     * Return the key used to register the post type with WordPress
     * First parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     */
    public static function getPostType(): string
    {
        return 'page';
    }
}
