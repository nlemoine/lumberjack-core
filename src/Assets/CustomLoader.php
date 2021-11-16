<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Exception\FileNotFoundException;
use Inpsyde\Assets\Loader\ArrayLoader;
use Symfony\Component\Asset\Packages;

class CustomLoader extends ArrayLoader
{
    private Packages $packages;

    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function load($resource): array
    {
        if (!\is_string($resource) || !\is_readable($resource)) {
            throw new FileNotFoundException(
                \sprintf(
                    'The given file "%s" does not exists or is not readable.',
                    (string) $resource
                )
            );
        }

        $data = require $resource;

        $assets = \array_map(
            [$this, 'resolveUrl'],
            (array) $data
        );

        return parent::load($assets);
    }

    protected function resolveUrl(array $asset): array
    {
        $asset['url'] = $this->packages->getUrl($asset['url']);
        return $asset;
    }
}
