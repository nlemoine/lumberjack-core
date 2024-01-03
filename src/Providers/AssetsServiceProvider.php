<?php

namespace Rareloop\Lumberjack\Providers;

use Inpsyde\Assets\AssetFactory;

use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Loader\ArrayLoader;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Rareloop\Lumberjack\Assets\CustomLoader;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;
use function DI\get;

class AssetsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Packages::class, function ($app) {
            $packages = $this->getConfig('assets.config.packages', []);
            $defaultVersion = $this->get('assets.version_strategy');

            foreach ($packages as $name => $package) {
                // $package['version_strategy'] = $package['version_strategy'] ?? null;
                // $package['json_manifest_path'] = $package['json_manifest_path'] ?? null;
                // // $package['strict_mode'] = $package['strict_mode'] ?? null;

                // if (null !== $package['version_strategy']) {
                //     $version = $this->createVersion(($package['version_strategy']);
                // } elseif (!\array_key_exists('version', $package) && null === $package['json_manifest_path']) {
                //     // if neither version nor json_manifest_path are specified, use the default
                //     $version = $defaultVersion;
                // } else {
                //     // let format fallback to main version_format
                //     $format = $package['version_format'] ?: $this->get('assets.version_fomat');
                //     $version = $package['version'] ?? null;
                //     $version = $this->createVersion($container, $version, $format, $package['json_manifest_path'], $name, $package['strict_mode']);
                // }

                $packages[$name] = $this->createPackage(
                    $this->getParameter($package['base_path'] ?? ''),
                    $this->getParameter($package['base_urls'] ?? []),
                    $defaultVersion
                );
            }

            return new Packages($this->get(PackageInterface::class), $packages);
        });
        $this->app->singleton('assets.packages', get(Packages::class));

        $this->app->singleton(PackageInterface::class, function ($app) {
            return $this->createPackage(
                $this->get('assets.base_path'),
                $this->get('assets.base_urls'),
                $this->get('assets.version_strategy')
            );
        });
        $this->app->singleton('assets.default_package', get(PackageInterface::class));

        $this->app->singleton('assets.version_strategy', function () {
            return $this->createVersion(
                $this->get('assets.version'),
                $this->get('assets.version_format'),
                $this->get('assets.json_manifest_path'),
                'default',
                $this->get('assets.strict_mode')
            );
        });

        $this->app->singleton('assets.strict_mode', $this->getConfig('assets.config.strict_mode', false));
        $this->app->singleton('assets.base_path', function () {
            return $this->getParameter($this->getConfig('assets.config.base_path', ''));
        });
        $this->app->singleton('assets.base_urls', function () {
            return $this->getParameter($this->getConfig('assets.config.base_urls', []));
        });
        $this->app->singleton('assets.version', $this->getConfig('assets.config.version', null));
        $this->app->singleton('assets.version_format', $this->getConfig('assets.config.version_format', '%%s?%%s'));
        $this->app->singleton('assets.json_manifest_path', $this->getParameter($this->getConfig('assets.config.json_manifest_path', null)));

        $this->app->singleton(AssetManager::class, AssetManager::class);
        $this->app->singleton(ArrayLoader::class, ArrayLoader::class);
        $this->app->singleton(AssetFactory::class, AssetFactory::class);
        $this->app->singleton(CustomLoader::class, CustomLoader::class);

        foreach ($this->getConfig('assets.assets', []) as $asset) {
            $typeMap = [
                Style::class  => 'css',
                Script::class => 'js',
            ];
            if (!isset($typeMap[$asset['type']])) {
                continue;
            }
            $asset['url'] = $this->get(PackageInterface::class)->getUrl($asset['url']);
            $id = \sprintf('assets.%s.%s', $typeMap[$asset['type']], $asset['handle']);
            $this->app->singleton($id, function () use ($asset) {
                return $this->get(AssetFactory::class)->create($asset)->disableAutodiscoverVersion();
            });
        }
    }

    public function boot()
    {
        $this->app->get(AssetManager::class)->setup();
    }

    /**
     * Create a package
     */
    private function createPackage(?string $basePath, array $baseUrls, VersionStrategyInterface $versionStrategy): PackageInterface
    {
        if ($basePath && $baseUrls) {
            throw new \LogicException('An asset package cannot have base URLs and base paths.');
        }
        if (!$baseUrls) {
            return new PathPackage($basePath, $versionStrategy);
        }

        return new UrlPackage($baseUrls, $versionStrategy);
    }

    /**
     * Create a version strategy
     *
     * @param boolean $strictMode
     */
    private function createVersion(?string $version, ?string $format, ?string $jsonManifestPath, string $name, bool $strictMode): VersionStrategyInterface
    {
        if ($version && $jsonManifestPath) {
            throw new \LogicException(\sprintf('Asset package "%s" cannot have version and manifest.', $name));
        }
        if ($version) {
            return new StaticVersionStrategy($version, $format);
        }
        if ($jsonManifestPath) {
            return new JsonManifestVersionStrategy($jsonManifestPath, null, $strictMode);
        }

        return new EmptyVersionStrategy();
    }
}
