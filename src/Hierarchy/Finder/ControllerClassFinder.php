<?php

namespace Rareloop\Lumberjack\Hierarchy\Finder;

use Brain\Hierarchy\Finder\FindFirstTrait;
use Brain\Hierarchy\Finder\TemplateFinder;
use function Symfony\Component\String\u;

class ControllerClassFinder implements TemplateFinder
{
    use FindFirstTrait;

    public function __construct(
        private array $namespaces = []
    ) {
        if (!count($this->namespaces)) {
            $this->namespaces[] = 'App\\Http\\Controllers\\';
        }
    }

    public function find(string $template, string $type): string
    {
        if ($template === '404') {
            $template = 'error-404';
        }
        $template = $template . '-controller';

        $controllerClass = u($template)->camel()->title();

        foreach ($this->namespaces as $namespace) {
            $controllerFqn = $namespace . $controllerClass;
            if (\class_exists($controllerFqn)) {
                return $controllerFqn;
            }
        }
        return '';
    }
}
