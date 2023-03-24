<?php

namespace Rareloop\Lumberjack\Models;

class FrontPage extends AbstractStaticPageType
{
    public static function getPageType(): string
    {
        return 'front_page';
    }
}
