<?php

namespace Rareloop\Lumberjack\Models;

class Attachment extends AbstractPostType
{
    /**
     * Return the key used to register the post type with WordPress
     * First parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     */
    public static function getPostType(): string
    {
        return 'attachment';
    }

    public static function query(array $args = []): array
    {
        $args['post_status'] = 'inherit';

        return parent::query($args);
    }
}
