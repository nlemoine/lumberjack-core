<?php

namespace Rareloop\Lumberjack\Providers;

use function DI\get;
use Inpsyde\Assets\AssetManager;
use Rareloop\Lumberjack\Assets\CustomLoader;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

class AssetsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Packages::class, function ($app) {
            $packages = [];
            foreach ($app->get('assets.named_packages') as $name => $package) {
                $version = $app->make('assets.strategy_factory', [
                    'version'          => isset($package['version']) ? $package['version'] : null,
                    'format'           => isset($package['version_format']) ? $package['version_format'] : null,
                    'jsonManifestPath' => isset($package['json_manifest_path']) ? $package['json_manifest_path'] : null,
                    'name'             => $name,
                ]);

                $packages[$name] = $app->make('assets.package_factory', [
                    'basePath' => isset($package['base_path']) ? $package['base_path'] : '',
                    'baseUrls' => isset($package['base_urls']) ? $package['base_urls'] : [],
                    'version'  => $version,
                    'name'     => $name,
                ]);
            }

            return new Packages($app->get('assets.default_package'), $packages);
        });
        $this->app->singleton('assets.packages', get(Packages::class));

        $this->app->singleton(PackageInterface::class, function ($app) {
            $version = $app->make('assets.strategy_factory', [
                'version'          => $app->get('assets.version'),
                'format'           => $app->get('assets.version_format'),
                'jsonManifestPath' => $app->get('assets.json_manifest_path'),
                'name'             => 'default',
            ]);

            return $app->make('assets.package_factory', [
                'basePath' => $app->get('assets.base_path'),
                'baseUrls' => $app->get('assets.base_urls'),
                'version'  => $version,
                'name'     => 'default',
            ]);
        });
        $this->app->bind('assets.default_package', get(PackageInterface::class));

        $this->app->bind('assets.base_path', '');
        $this->app->bind('assets.version', null);
        $this->app->bind('assets.version_format', null);
        $this->app->singleton('assets.json_manifest_path', function () {
            if (WP_DEBUG || !\in_array(WP_ENV, ['staging', 'production'], true)) {
                return null;
            }
            $manifest_path = $this->app->get('path.assets') . '/manifest.json';
            if (!\is_file($manifest_path)) {
                return null;
            }
            return $manifest_path;
        });

        // $this->app->bind('assets.json_manifest_path', null);
        // $this->app->bind('assets.named_packages', []);

        // prototypes
        $this->app->bind('assets.strategy_factory', function ($version, $format, $jsonManifestPath, $name) {
            if ($version && $jsonManifestPath) {
                throw new \LogicException(\sprintf('Asset package "%s" cannot have version and manifest.', $name));
            }
            if ($version) {
                return new StaticVersionStrategy($version, $format);
            }
            if ($jsonManifestPath) {
                return new JsonManifestVersionStrategy($jsonManifestPath);
            }

            return new EmptyVersionStrategy();
        });

        $this->app->bind('assets.package_factory', function ($basePath, $baseUrls, $version, $name) {
            if ($basePath && $baseUrls) {
                throw new \LogicException(\sprintf('Asset package "%s" cannot have base URLs and base paths.', $name));
            }
            if (!$baseUrls) {
                return new PathPackage($basePath, $version);
            }

            return new UrlPackage($baseUrls, $version);
        });

        $this->app->singleton(AssetManager::class, AssetManager::class);
        $this->app->singleton(CustomLoader::class, CustomLoader::class);

        $this->app->singleton('assets', function () {
            $loader = $this->app->get(CustomLoader::class);

            $loader->disableAutodiscoverVersion();
            $assets = $loader->load($this->app->get('path.config') . '/assets.php');
            return $assets;
        });

        $this->app->singleton('assets.base_urls', $this->app->get('url.assets'));
        $this->app->bind('assets.named_packages', [
            'path' => [
                'base_path' => $this->app->get('path.assets'),
            ],
            'editor' => [
                'base_path' => 'assets',
            ],
        ]);

        // $this->app->bind('assets.loader', function () {
        //     $loader = new ArrayLoader();
        //     $loader->disableAutodiscoverVersion();
        //     $config = $this->app->get(Config::class)->get('assets');
        //     /** @var Asset[] $assets */
        //     return $loader->load(\array_map(function ($asset) {
        //         $asset['url'] = $this->app->get('assets.packages')->getUrl($asset['url']);

        //         return $asset;
        //     }, $config));
        // });
    }

    public function boot()
    {
        $this->app->get(AssetManager::class)->setup();
    }
}
