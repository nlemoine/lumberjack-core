<?php

namespace Rareloop\Lumberjack\Test\Models;

use PHPUnit\Framework\TestCase;
use Rareloop\Lumberjack\Models\Page;

class PageTest extends TestCase
{
    public function testPageClassHasCorrectPostType()
    {
        $this->assertSame('page', Page::getPostType());
    }
}
