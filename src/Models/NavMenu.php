<?php

namespace Rareloop\Lumberjack\Models;

use Timber\Menu as TimberMenu;

class NavMenu extends TimberMenu
{
    public $MenuItemClass = NavMenuItem::class;

    public $PostClass = Post::class;
}
