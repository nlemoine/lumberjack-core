<?php

namespace Rareloop\Lumberjack\Providers;

use App\Asset\Helper;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\AssetFactory;
use Inpsyde\Assets\Loader\ArrayLoader;
use Rareloop\Lumberjack\Config;
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
        $this->app->bind('assets.packages', function ($app) {
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

        $this->app->bind('assets.default_package', function ($app) {
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

        $this->app->bind('assets.base_path', '');
        $this->app->bind('assets.base_urls', []);
        $this->app->bind('assets.version', null);
        $this->app->bind('assets.version_format', null);
        $this->app->bind('assets.json_manifest_path', null);
        $this->app->bind('assets.named_packages', []);

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

        $this->app->bind('assets.helper', function () {
            return new Helper($this->app->get('assets.packages')->getPackage('path'));
        });

        $this->app->bind('assets.loader', function () {
            $loader = new ArrayLoader();
            $loader->disableAutodiscoverVersion();
            $config = $this->app->get(Config::class)->get('assets');
            // @var Asset[] $assets
            return $loader->load(\array_map(function ($asset) {
                $asset['url'] = $this->app->get('assets.packages')->getUrl($asset['url']);

                return $asset;
            }, $config));
        });

        $this->app->singleton('assets.store', function ($app) {
            $packages = $app->get('assets.packages');

            return new class($packages) extends \ArrayObject {
                private $packages;

                public function __construct(Packages $packages)
                {
                    $this->packages = $packages;
                }

                public function append($asset)
                {
                    if (isset($asset['url'])) {
                        $asset['url'] = $this->packages->getUrl($asset['url']);
                    }
                    parent::append(AssetFactory::create($asset)->disableAutodiscoverVersion());
                }
            };
        });

    }

    public function boot(Config $config)
    {
        $this->app->bind('assets.base_urls', $this->app->get('url.assets'));
        $this->app->bind('assets.named_packages', [
            'path' => [
                'base_path' => $this->app->get('path.assets'),
                'version'   => '',
            ],
            'editor' => [
                'base_path' => 'assets',
            ],
        ]);

        if (!WP_DEBUG && \in_array(WP_ENV, ['staging', 'production'], true)) {
            $manifest_path = $this->app->get('path.assets') . '/manifest.json';
            $this->app->bind('assets.json_manifest_path', $manifest_path);
            $this->app->bind('assets.named_packages', \array_merge_recursive($this->app->get('assets.named_packages'), [
                'path',
            ]));
        }

        foreach ($config->get('assets', []) as $asset) {
            $this->app->get('assets.store')->append($asset);
        }

        // Enqueue scripts & styles
        \add_action(
            AssetManager::ACTION_SETUP,
            function (AssetManager $assetManager) {
                $assets = $this->app->get('assets.store');
                foreach ($assets as $asset) {
                    $assetManager->register($asset);
                }
            }
        );
    }
}
