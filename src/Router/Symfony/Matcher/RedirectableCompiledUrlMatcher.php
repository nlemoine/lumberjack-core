<?php

namespace Rareloop\Lumberjack\Router\Symfony\Matcher;

use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;

class RedirectableCompiledUrlMatcher extends CompiledUrlMatcher implements RedirectableUrlMatcherInterface
{
    public function redirect(string $path, string $route, string $scheme = null): array
    {
        \wp_safe_redirect(\home_url($path), 301);
        exit;
    }
}
