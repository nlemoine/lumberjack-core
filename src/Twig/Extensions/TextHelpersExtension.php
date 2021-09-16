<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextHelpersExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('half', [$this, 'splitText'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function splitText($text, $format = false)
    {
        if (!$this->hasSpace($text)) {
            return $text;
        }

        $words = \explode(' ', $text);

        $words_first = [];
        $words_second = [];
        $length = \mb_strlen(\str_replace(' ', '', $text));
        foreach ($words as $word) {
            if (\mb_strlen(\implode('', $words_first)) <= ($length / 2 - 4)) {
                $words_first[] = $word;
            } else {
                $words_second[] = $word;
            }
        }

        $first = \implode(' ', $words_first) . ' ';
        $second = \implode(' ', $words_second);

        if ($format) {
            return \sprintf($format, $first, $second);
        }

        return \array_map('trim', [
            $first,
            $second,
        ]);
    }

    private function hasSpace($text)
    {
        return \strpos($text, ' ') !== false;
    }
}
