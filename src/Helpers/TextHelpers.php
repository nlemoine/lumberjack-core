<?php

namespace Rareloop\Lumberjack\Helpers;

class TextHelpers
{

    public const NO_BREAK_SPACE = "\xC2\xA0"; // &#160;
    public const ALL_SPACES = "\xE2\x80\xAF|\xC2\xAD|\xC2\xA0|\\s"; // All supported spaces, used in regexps. Better than \s

    /**
     * Bisect text into two equal parts
     *
     * @param string $text
     * @param string $format
     * @param float $center
     * @return string[]|string
     */
    public static function bisect(string $text, string $format = '', float $center = 0.4)
    {
        if (!self::containsSpace($text)) {
            return $text;
        }

        $words = \preg_split('@['.self::ALL_SPACES.']@mu', $text);
        $words_first = [];
        $words_second = [];

        $text_without_spaces = preg_replace('@['.self::ALL_SPACES.']@mu', '', $text);
        $length = \mb_strlen($text_without_spaces);
        $middle = (float) ($length * $center);
        foreach ($words as $word) {
            if (\mb_strlen(\implode('', $words_first)) <= $middle) {
                $words_first[] = $word;
            } else {
                $words_second[] = $word;
            }
        }

        $first = \implode(' ', $words_first) . ' ';
        $second = \implode(' ', $words_second);

        if (!empty($format)) {
            return \sprintf($format, $first, $second);
        }

        return \array_map('trim', [
            $first,
            $second,
        ]);

    }

    /**
     * Check if a string contains a space
     *
     * @param string $text
     * @return boolean
     */
    public static function containsSpace(string $text): bool
    {
        return (bool) \preg_match('@['.self::ALL_SPACES.']@mu', $text);
    }
}
