<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Rareloop\Lumberjack\Helpers\TextHelpers;

final class TextHelpersExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('bisect', [TextHelpers::class, 'bisect'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('obfuscate', 'antispambot', [
                'is_safe' => ['html'],
            ]),
        ];
    }

}
