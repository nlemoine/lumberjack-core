<?php

namespace Rareloop\Lumberjack\Router\Symfony;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CurrentRoute
{
    public function __construct(
        private array $currentRoute,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    private function __get(string $property): mixed
    {
        if ($property === 'canonical') {
            return $this->getCanonical();
        }

        return $this->currentRoute['_' . $property] ?? null;
    }

    public function getCanonical(): string
    {
        return $this->urlGenerator->generate(
            $this->currentRoute['_route'] ?? '',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
