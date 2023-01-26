<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\Exception\FileNotFoundException;
use Inpsyde\Assets\Exception\InvalidResourceException;
use Inpsyde\Assets\Loader\EncoreEntrypointsLoader;

class BudEntrypointsLoader extends EncoreEntrypointsLoader
{
    /**
     * @param mixed $resource
     *
     * @return array
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
     * @psalm-suppress MixedArgument
     */
    public function load($resource, array $entrypoints = []): array
    {
        if (!\is_string($resource) || !\is_readable($resource)) {
            throw new FileNotFoundException(
                \sprintf(
                    'The given file "%s" does not exists or is not readable.',
                    (string) $resource
                )
            );
        }

        $data = @\file_get_contents($resource)
            ?: ''; // phpcs:ignore
        $data = \json_decode($data, true);
        $errorCode = \json_last_error();
        if ($errorCode > 0) {
            throw new InvalidResourceException(
                \sprintf('Error parsing JSON - %s', $this->getJSONErrorMessage($errorCode))
            );
        }

        return $this->parseData($data, $resource, $entrypoints);
    }

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

    /**
     * Translates JSON_ERROR_* constant into meaningful message.
     *
     * @return string Message string
     */
    private function getJSONErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}
