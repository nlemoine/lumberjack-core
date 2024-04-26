<?php

namespace Rareloop\Lumberjack\Assets;

use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\Asset\Exception\RuntimeException;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class ViteAssetVersionStrategy implements VersionStrategyInterface
{
    private string $manifestPath;

    private string $basePath;

    private array $manifestData;

    private array $entrypointsData;

    private ?string $viteMode;

    public function __construct(
        string $manifestPath,
        private bool $strictMode = false
    ) {
        $this->manifestPath = $manifestPath;
        $this->basePath = \dirname($manifestPath);
    }

    /**
     * With a entrypoints, we don't really know or care about what
     * the version is. Instead, this returns the path to the
     * versioned file. as it contains a hashed and different path
     * with each new config, this is enough for us.
     */
    public function getVersion(string $path): string
    {
        return $this->applyVersion($path);
    }

    public function applyVersion(string $path): string
    {
        return $this->getAssetPath($path) ?: $path;
    }

    private function getAssetPath(string $path): ?string
    {
        $entrypointsData = $this->getEntrypointsData();

        $this->viteMode ??= isset($entrypointsData['viteServer']) && $entrypointsData['viteServer'] ? 'dev' : 'build';
        if ('build' === $this->viteMode) {
            $manifestData = $this->getManifestData();
            if (isset($manifestData[$path]['file'])) {
                return $entrypointsData['base'] . $manifestData[$path]['file'];
            }
        } else {
            return $entrypointsData['viteServer'] . $entrypointsData['base'] . $path;
        }

        if ($this->strictMode) {
            $message = \sprintf('Asset "%s" not found in manifest "%s".', $path, $this->manifestPath);
            $alternatives = $this->findAlternatives($path, $this->manifestData);
            if (\count($alternatives) > 0) {
                $message .= \sprintf(' Did you mean one of these? "%s".', \implode('", "', $alternatives));
            }

            throw new AssetNotFoundException($message, $alternatives);
        }

        return null;
    }

    private function getEntrypointsData(): array
    {
        if (!isset($this->entrypointsData)) {
            $entrypointsPath = $this->basePath . '/entrypoints.json';
            $entrypointData = \wp_json_file_decode($this->basePath . '/entrypoints.json', [
                'associative' => true,
            ]);
            if ($entrypointData === null) {
                throw new RuntimeException(\sprintf('Error parsing JSON from asset entrypoints file "%s". The file either does not exists, is not readable or contains invalid JSON.', $entrypointsPath));
            }
            $this->entrypointsData = $entrypointData;
        }

        return $this->entrypointsData;
    }

    private function getManifestData(): array
    {
        if (!isset($this->manifestData)) {
            $manifestData = \wp_json_file_decode($this->manifestPath, [
                'associative' => true,
            ]);
            if ($manifestData === null) {
                throw new RuntimeException(\sprintf('Error parsing JSON from asset manifest file "%s". The file either does not exists, is not readable or contains invalid JSON.', $this->manifestPath));
            }
            $this->manifestData = $manifestData;
        }

        return $this->manifestData;
    }

    private function findAlternatives(string $path, array $manifestData): array
    {
        $path = \strtolower($path);
        $alternatives = [];

        foreach ($manifestData as $key => $value) {
            $lev = \levenshtein($path, \strtolower($key));
            if ($lev <= \strlen($path) / 3 || false !== \stripos($key, $path)) {
                $alternatives[$key] = isset($alternatives[$key]) ? \min($lev, $alternatives[$key]) : $lev;
            }

            $lev = \levenshtein($path, \strtolower($value));
            if ($lev <= \strlen($path) / 3 || false !== \stripos($key, $path)) {
                $alternatives[$key] = isset($alternatives[$key]) ? \min($lev, $alternatives[$key]) : $lev;
            }
        }

        \asort($alternatives);

        return \array_keys($alternatives);
    }
}
