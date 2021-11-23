<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

use Rareloop\Lumberjack\Helpers\ImageHelpers;
use Symfony\Component\Asset\PathPackage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SvgHelpersExtension extends AbstractExtension
{
    private PathPackage $package;

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

    public function inlineSvg(string $file, array $attributes = []): string
    {
        $svg = $this->getFileContents($file);
        if (!empty($attributes)) {
            $svg = \str_replace('<svg', \sprintf('<svg%s', $this->renderAttributes($attributes)), $svg);
        }

        return $svg;
    }

    public function getFileContents(string $path, ?string $packageName = null): ?string
    {
        $file = \parse_url($this->package->getUrl($path, $packageName), PHP_URL_PATH);
        if (!\is_file($file)) {
            return null;
        }

        return \file_get_contents($file);
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
