<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Rareloop\Lumberjack\Helpers\ImageHelpers;
use Symfony\Component\Asset\PathPackage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgHelpersExtension extends AbstractExtension
{
    private PathPackage $package;

    private array $rendered = [];

    public function __construct(PathPackage $package)
    {
        $this->package = $package;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('inline_svg', [$this, 'inlineSvg'], [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('svg_placeholder', [ImageHelpers::class, 'getSvgPlaceholder']),
        ];
    }

    public function inlineSvg(string $file, array $attributes = [], array $args = []): string
    {
        $args = \array_merge([
            'renderOnce' => false,
        ], $args);

        $svg_path = $this->package->getUrl($file);

        if (isset($this->rendered[$svg_path]) && !empty($this->rendered[$svg_path]['once'])) {
            return '';
        }

        $symbol = '';
        $svg = '';
        $svg_id = \pathinfo($file, PATHINFO_FILENAME);

        // TODO store viewBox and width/height from SVG to set on use
        if (isset($this->rendered[$svg_path]) && empty($this->rendered[$svg_path]['once'])) {
            if (!empty($this->rendered[$svg_path]['attributes'])) {
                $attributes = \array_merge($attributes, $this->rendered[$svg_path]['attributes']);
            }
        } else {
            $svg = $this->getFileContents($svg_path);
            if (!$svg) {
                return '';
            }
            $attrs = [];

            \preg_match('@viewBox="([^"]+)"@', $svg, $matches);
            if (isset($matches[1]) && empty($attributes['viewBox'])) {
                $attrs['viewBox'] = $matches[1];
                $attributes = \array_merge($attributes, $attrs);
            }
            $this->rendered[$svg_path]['attributes'] = $attrs;
            $this->rendered[$svg_path]['once'] = $args['renderOnce'];

            $symbol = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0">';
            $symbol .= '<symbol';
            $symbol .= ' id="' . $svg_id . '"';
            if (isset($attrs['viewBox'])) {
                $symbol .= ' viewBox="' . $attrs['viewBox'] . '"';
            }
            $symbol .= '>';
            $symbol = \preg_replace("/<svg[^>]*?(\/?)>/si", $symbol, $svg);
            $symbol = \str_replace('</svg>', '</symbol></svg>', $symbol);
        }

        $svg = <<<SVG
        <svg><use xlink:href="#{$svg_id}"/></svg>
        SVG;

        $svg = \str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svg);
        $symbol = \str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $symbol);
        if (!empty($attributes)) {
            $svg = \str_replace('<svg', \sprintf('<svg%s', $this->renderAttributes($attributes)), $svg);
        }

        return $symbol . $svg;
    }

    public function getFileContents(string $path): ?string
    {
        if (!\is_file($path)) {
            return null;
        }

        return \file_get_contents($path);
    }

    /**
     * Render attributes
     *
     * @param array<string> $attributes
     */
    protected function renderAttributes(array $attributes): string
    {
        $attrs = \array_map(function ($attribute, $value) {
            return \is_string($value) ? \sprintf('%s="%s"', $attribute, $value) : $attribute;
        }, \array_keys($attributes), $attributes);

        return ' ' . \implode(' ', $attrs);
    }
}
