<?php

namespace Rareloop\Lumberjack\Twig\Extensions;

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
            new TwigFunction('placeholder_svg', [$this, 'placeholderSvg']),
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

    public function placeholderSvg(int $width, int $height, bool $fill = null, bool $base64 = false): string
    {
        $style = $fill ? \sprintf('style="background:%s"', $fill) : '';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" {$style}></svg>
SVG;

        if ($base64) {
            return \sprintf('data:image/svg+xml;base64,%s', \base64_encode($svg));
        }

        return \sprintf('data:image/svg+xml,%s', $this->encodeOptimizedSVGDataUri($svg));
    }

    protected function encodeOptimizedSVGDataUri(string $uri): string
    {
        // First, uri encode everything
        $uri = \rawurlencode($uri);
        $replacements = [
            // remove newlines
            '/%0A/' => '',
            // put spaces back in
            '/%20/' => ' ',
            // put equals signs back in
            '/%3D/' => '=',
            // put colons back in
            '/%3A/' => ':',
            // put slashes back in
            '/%2F/' => '/',
            // replace quotes with apostrophes (may break certain SVGs)
            '/%22/' => "'",
        ];
        foreach ($replacements as $pattern => $replacement) {
            $uri = \preg_replace($pattern, $replacement, $uri);
        }

        return $uri;
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
