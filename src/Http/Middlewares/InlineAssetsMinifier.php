<?php

namespace Rareloop\Lumberjack\Http\Middlewares;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Throwable;
use voku\helper\HtmlMinDomObserverInterface;
use voku\helper\HtmlMinInterface;
use voku\helper\SimpleHtmlDomInterface;

/**
 * Minifies the content of inline <script> and <style> elements and of style attributes while HtmlMin walks the DOM.
 */
final class InlineAssetsMinifier implements HtmlMinDomObserverInterface
{
    /**
     * Script types whose content is minified. Anything else (modules, templates, importmaps...) is left untouched.
     */
    private const SCRIPT_TYPES = ['', 'text/javascript', 'application/javascript', 'application/ld+json'];

    public function domElementBeforeMinification(SimpleHtmlDomInterface $element, HtmlMinInterface $htmlMin): void
    {
        if ($element->tag === 'script') {
            if (\in_array(\strtolower(\trim($element->getAttribute('type'))), self::SCRIPT_TYPES, true)) {
                $this->replaceContent($element, $this->minifyJs($element->innerhtml));
            }

            return;
        }

        if ($element->tag === 'style') {
            $this->replaceContent($element, $this->minifyCss($this->stripHtmlComment($element->innerhtml)));

            return;
        }

        if ($element->hasAttribute('style')) {
            $style = $element->getAttribute('style');
            $minified = $this->minifyInlineCss($style);
            if ($minified !== '' && \strlen($minified) < \strlen($style)) {
                $element->setAttribute('style', $minified);
            }
        }
    }

    public function domElementAfterMinification(SimpleHtmlDomInterface $element, HtmlMinInterface $htmlMin): void
    {
    }

    /**
     * Rebuilds the element with its minified content, only when minification actually made it smaller.
     */
    private function replaceContent(SimpleHtmlDomInterface $element, string $minified): void
    {
        if ($minified === '' || \strlen($minified) >= \strlen($element->innerhtml)) {
            return;
        }

        $attributes = '';
        foreach ($element->getAllAttributes() ?? [] as $name => $value) {
            $attributes .= ' ' . $name . '="' . \htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5) . '"';
        }

        $element->outerhtml = '<' . $element->tag . $attributes . '>' . $minified . '</' . $element->tag . '>';
    }

    private function minifyJs(string $source): string
    {
        try {
            return (new JS($source))->minify();
        } catch (Throwable) {
            return '';
        }
    }

    private function minifyCss(string $source): string
    {
        try {
            return (new CSS($source))->minify();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Minifies a style attribute by wrapping it in a dummy rule and stripping it back.
     */
    private function minifyInlineCss(string $declarations): string
    {
        $minified = $this->minifyCss('a{' . $declarations . '}');

        return \str_starts_with($minified, 'a{') && \str_ends_with($minified, '}') ? \substr($minified, 2, -1) : '';
    }

    /**
     * Strips the legacy <!-- --> wrapper some themes still put around inline CSS.
     */
    private function stripHtmlComment(string $css): string
    {
        $css = \trim($css);
        if (\str_starts_with($css, '<!--')) {
            $css = \substr($css, 4);
        }
        if (\str_ends_with($css, '-->')) {
            $css = \substr($css, 0, -3);
        }

        return $css;
    }
}
