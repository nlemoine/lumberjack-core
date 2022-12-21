<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Loader\EncoreEntrypointsLoader;

class BudEntrypointsLoader extends EncoreEntrypointsLoader
{
    public function loadPoint($resource, string $handle): array
    {
        $assets = $this->load($resource);
        return \array_values(\array_filter($assets, function ($asset) use ($handle) {
            return $asset->handle() === $handle;
        }));
    }

    protected function parseData(array $data, string $resource): array
    {
        $directory = \trailingslashit(\dirname($resource));
        /** @var array{css:string[], js:string[]} $data */
        $data = \is_array($data) ? $data : [];

        $assets = [];
        foreach ($data as $handle => $filesByExtension) {
            $files = $filesByExtension['css'] ?? [];
            $assets = \array_merge($assets, $this->extractAssets($handle, $files, $directory));

            $files = $filesByExtension['js'] ?? [];
            $assets = \array_merge($assets, $this->extractAssets($handle, $files, $directory));
        }

        return $assets;
    }
}
