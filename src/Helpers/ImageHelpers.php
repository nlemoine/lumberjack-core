<?php

namespace Rareloop\Lumberjack\Helpers;

class ImageHelpers
{
    public static function getSvgPlaceholder(int $width = 300, int $height = 300, ?string $fill = null, bool $base64 = false): string
    {
        $style = $fill ? \sprintf('style="background:%s"', $fill) : '';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" {$style}></svg>
SVG;

        if ($base64) {
            return \sprintf('data:image/svg+xml;base64,%s', \base64_encode($svg));
        }

        return \sprintf('data:image/svg+xml,%s', self::encodeOptimizedSVGDataUri($svg));
    }

    public static function encodeOptimizedSVGDataUri(string $uri): string
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
}
