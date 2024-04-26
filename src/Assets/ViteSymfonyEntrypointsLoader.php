<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\Loader\EncoreEntrypointsLoader;
use Inpsyde\Assets\Style;
use Inpsyde\Assets\BaseAsset;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Exception\FileNotFoundException;
use Exception;

class ViteSymfonyEntrypointsLoader extends EncoreEntrypointsLoader
{

    private const VITE_CLIENT_HANDLE = 'vite-client';
    private const VITE_CLIENT_SCRIPT = '@vite/client';

    protected array $entrypointsData;

    public function __construct(
        private string $entrypointsPath
    )
    {
        if (!\is_readable($this->entrypointsPath)) {
            throw new FileNotFoundException(
                sprintf(
                    'The given file "%s" does not exists or is not readable.',
                    $this->entrypointsPath
                )
            );
        }
    }

    public function load($entrypointName = null): array
    {
        return $this->parseData($this->getEntrypointsData(), $entrypointName);
    }

    protected function getEntrypointsData(): array
    {
        if (isset($this->entrypointsData)) {
            return $this->entrypointsData;
        }

        $data = wp_json_file_decode($this->entrypointsPath, ['associative' => true]);
        if ($data === null) {
            throw new Exception(
                sprintf(
                    'Error parsing JSON from asset entrypoints file "%s". The file either does not exists, is not readable or contains invalid JSON.',
                    $this->entrypointsPath
                )
            );
        }

        return $this->entrypointsData = $data;
    }

    /**
     * Undocumented function
     *
     * @param array{entryPoints: array, viteServer: string|null, base: string} $data
     * @param string|null $entrypointName
     * @return array
     */
    protected function parseData(array $data, ?string $entrypointName = null): array
    {
        $viteServer = $data['viteServer'] ?? null;
        if ($viteServer) {
            $base = $data['base'] ?? '';
            // vite client
            $viteClient = new Script(
                self::VITE_CLIENT_HANDLE,
                $viteServer . $base . self::VITE_CLIENT_SCRIPT
            );
            $viteClient
                ->withAttributes([
                    'type' => 'module',
                ])
                ->disableAutodiscoverVersion()
                ->canEnqueue(false)
            ;
        }

        $assets = [];
        // Single entrypoint
        if ($entrypointName !== null && isset($data['entryPoints'][$entrypointName])) {
            $assets = $this->getAssets($entrypointName, $data['entryPoints'][$entrypointName]);
        // All entrypoints
        } else {
            foreach ($data['entryPoints'] as $name => $entrypointData) {
                array_push($assets, ...$this->getAssets($name, $entrypointData));
            }
        }

        if (isset($viteClient)) {
            foreach ($assets as $asset) {
                if (!$asset instanceof Script) {
                    continue;
                }
                // Add vite-client dependency to all scripts
                $asset->withDependencies(self::VITE_CLIENT_HANDLE);
            }
            // Add vite-checker to the end of the assets
            array_push($assets, $viteClient);
        }

        if ($viteServer) {
            $assets = array_map(
                static function (Asset $asset): Asset {
                    if ($asset instanceof Script) {
                        $asset->isInHeader();
                        $asset->withAttributes([
                            'defer' => false,
                        ]);
                    }
                    return $asset;
                },
                $assets
            );
        }

        return $assets;
    }

    protected function getAssets(string $entrypointName, array $entrypointData): array
    {
        $assets = [];
        $directory = $_SERVER['DOCUMENT_ROOT'] ?? '';
        foreach ($entrypointData as $type => $entrypoint) {
            if (!in_array($type, ['css', 'js'], true)) {
                continue;
            }

            $extractedAssets = $this->extractAssets($entrypointName, $entrypoint, $directory);
            if ($type === 'js') {
                $extractedAssets = \array_map(function ($asset) {
                    $asset->withAttributes([
                        'type'  => 'module',
                        'defer' => true,
                    ]);
                    return $asset;
                }, $extractedAssets);
            }

            array_push($assets, ...$extractedAssets);
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
            $handle = $i > 0
                ? "{$handle}-{$i}"
                : $handle;

            $sanitizedFile = $this->sanitizeFileName($file);

            $fileUrl = (!$this->directoryUrl || \parse_url($file, PHP_URL_HOST))
                ? $file
                : $this->directoryUrl . $sanitizedFile;

            $filePath = $directory . '/' . ltrim($sanitizedFile, '/');

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

    protected function buildAsset(string $handle, string $fileUrl, string $filePath): ?Asset
    {
        $extensionsToClass = [
            'css' => Style::class,
            'scss' => Style::class,
            'js' => Script::class,
        ];

        /** @var array{filename?:string, extension?:string} $pathInfo */
        $pathInfo = pathinfo($filePath);
        $filename = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? '';

        if (!in_array($extension, array_keys($extensionsToClass), true)) {
            return null;
        }

        $class = $extensionsToClass[$extension];

        /** @var Asset|BaseAsset $asset */
        $asset = new $class($handle, $fileUrl, $this->resolveLocation($filename));
        $asset->withFilePath($filePath);
        $asset->canEnqueue(true);

        if ($asset instanceof BaseAsset) {
            $this->autodiscoverVersion
                ? $asset->enableAutodiscoverVersion()
                : $asset->disableAutodiscoverVersion();
        }

        return $asset;
    }
}
