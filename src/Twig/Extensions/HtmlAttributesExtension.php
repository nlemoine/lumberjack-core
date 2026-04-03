<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HtmlAttributesExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('mergeAttr', [$this, 'mergeAndRender'], ['is_safe' => ['html']]),
            new TwigFunction('attr', [$this, 'renderAll'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render an associative array as HTML tag attributes.
     *
     * @param array<int|string, string> $attributes
     */
    public function renderAll(array $attributes): string
    {
        $result = '';

        foreach ($attributes as $name => $value) {
            $result .= \is_int($name)
                ? self::renderAttribute($value)
                : self::renderAttribute($name, $value);
        }

        return $result;
    }

    /**
     * Merge multiple attribute arrays and render as HTML attributes.
     */
    public function mergeAndRender(): string
    {
        $arrays = \func_get_args();
        $merged = [];

        foreach ($arrays as $array) {
            $merged = self::mergeRecursive($merged, $array);
        }

        return $this->renderAll($merged);
    }

    private static function renderAttribute(string $name, string $value = ''): string
    {
        if (\in_array($name, ['class', 'style'], true) && $value === '') {
            return '';
        }

        if ($value === '') {
            return ' ' . $name;
        }

        return ' ' . $name . '="' . \htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8') . '"';
    }

    private static function mergeRecursive(array $arr1, array $arr2): array
    {
        foreach ($arr2 as $key => $v) {
            if (\is_array($v)) {
                $arr1[$key] = isset($arr1[$key]) ? self::mergeRecursive($arr1[$key], $v) : $v;
            } else {
                $arr1[$key] = isset($arr1[$key]) ? $arr1[$key] . ($arr1[$key] !== $v ? ' ' . $v : '') : $v;
            }
        }

        return $arr1;
    }
}
