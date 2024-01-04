<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @property-read string $canonical
 * @property-read string $name
 */
class CurrentRoute
{
    public function __construct(
        private array $currentRoute,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'canonical':
                return $this->getCanonical();
            case 'name':
                return $this->getName();
        }

        return $this->currentRoute['_' . $property] ?? null;
    }

    public function __isset(string $property): bool
    {
        return isset($this->currentRoute['_' . $property]);
    }

    public function getCanonical(array $params = []): string
    {
        return $this->urlGenerator->generate(
            $this->currentRoute['_route'] ?? '',
            array_merge($this->getParams(), $params),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getName(): string
    {
        return $this->currentRoute['_route'] ?? '';
    }

    private function getParams(): array
    {
        return array_values(array_filter($this->currentRoute, fn ($key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY));
    }
}
