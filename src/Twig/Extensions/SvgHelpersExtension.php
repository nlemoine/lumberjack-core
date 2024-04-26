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

    public function getFunctions(): array
    {
        return [
            new TwigFunction('svg', [$this, 'inlineSvg'], [
                'is_safe' => ['html'],
            ]),
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
            'noSymbol'   => false,
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

            // Symbol stuff
            if (!$args['noSymbol']) {
                if (\strpos($svg, 'preserveAspectRatio=') !== false) {
                    \preg_match('@preserveAspectRatio="([^"]+)"@', $svg, $matches);
                    if (isset($matches[1]) && empty($attributes['preserveAspectRatio'])) {
                        $attrs['preserveAspectRatio'] = $matches[1];
                        $attributes = \array_merge($attributes, $attrs);
                    }
                }

                if (\strpos($svg, 'viewBox=') !== false) {
                    \preg_match('@viewBox="([^"]+)"@', $svg, $matches);
                    if (isset($matches[1]) && empty($attributes['viewBox'])) {
                        $attrs['viewBox'] = $matches[1];
                        $attributes = \array_merge($attributes, $attrs);
                    }
                }

                $this->rendered[$svg_path]['attributes'] = $attrs;
                $this->rendered[$svg_path]['once'] = $args['renderOnce'];

                $symbol = '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0">';
                $symbol .= '<symbol';
                $symbol .= ' id="' . $svg_id . '"';
                if (isset($attrs['viewBox'])) {
                    $symbol .= ' viewBox="' . $attrs['viewBox'] . '"';
                }
                if (isset($attrs['preserveAspectRatio'])) {
                    $symbol .= ' preserveAspectRatio="' . $attrs['preserveAspectRatio'] . '"';
                }
                $symbol .= '>';
                $symbol = \preg_replace("/<svg[^>]*?(\/?)>/si", $symbol, $svg);
                $symbol = \str_replace('</svg>', '</symbol></svg>', $symbol);
            }
        }

        if (!$args['noSymbol']) {
            $svg = <<<SVG
            <svg><use xlink:href="#{$svg_id}"/></svg>
            SVG;
        }

        $svg = \str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svg);
        $symbol = \str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $symbol);
        if (!empty($attributes)) {
            $svg = \str_replace('<svg', \sprintf('<svg%s', $this->renderAttributes($attributes)), $svg);
        }

        if ($args['noSymbol']) {
            return $svg;
        }

        return $symbol . $svg;
    }

    public function getFileContents(string $path): ?string
    {
        if (
            \str_starts_with($path, 'https://')
            || \str_starts_with($path, 'http://')
        ) {
            $stream_context = [
                "ssl" => [
                    "verify_peer"      => false,
                    "verify_peer_name" => false,
                ],
            ];
            return \file_get_contents($path, false, \stream_context_create($stream_context));
        }

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
