<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Rareloop\Lumberjack\Helpers\TextHelpers;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextHelpersExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('bisect', [TextHelpers::class, 'bisect'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('longest_word', [TextHelpers::class, 'longestWord'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('obfuscate', 'antispambot', [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
