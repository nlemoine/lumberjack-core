<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\Loader\EncoreEntrypointsLoader;

class BudEntrypointsLoader extends EncoreEntrypointsLoader
{
    protected function parseData(array $data, string $resource, array $entrypoints = []): array
    {
        $directory = \trailingslashit(\dirname($resource));
        /** @var array{css:string[], js:string[]} $data */
        $data = \is_array($data) ? $data : [];
        if (!empty($entrypoints)) {
            $data = \array_filter($data, static function (string $handle) use ($entrypoints) {
                return \in_array($handle, $entrypoints, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        $assets = [];
        foreach ($data as $handle => $filesByExtension) {
            $files = $filesByExtension['css'] ?? [];
            $assets = \array_merge($assets, $this->extractAssets($handle, $files, $directory));

            $files = $filesByExtension['js'] ?? [];
            $assets = \array_merge($assets, $this->extractAssets($handle, $files, $directory));
        }

        return $assets;
    }

    /**
     * @param string[] $files
     */
    protected function extractAssets(string $handle, array $files, string $directory): array
    {
        $assets = [];

        foreach ($files as $i => $file) {
            $sanitizedFile = $this->sanitizeFileName($file);

            $fileUrl = (!$this->directoryUrl)
                ? $file
                : $this->directoryUrl . $sanitizedFile;

            $handle = \md5($fileUrl);

            $filePath = $directory . $sanitizedFile;

            $asset = $this->buildAsset($handle, $fileUrl, $filePath);

            if ($asset !== null) {
                $assets[] = $asset;
            }
        }

        foreach ($assets as $i => $asset) {
            $dependencies = \array_map(
                static function (Asset $asset): string {
                    return $asset->handle();
                },
                \array_slice($assets, 0, $i)
            );
            $asset->withDependencies(...$dependencies);
        }

        return $assets;
    }
}
