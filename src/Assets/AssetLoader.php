<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;

class AssetLoader
{
    public function __construct(
        private ViteSymfonyEntrypointsLoader $viteLoader
    ) {
    }

    public function enqueueEntrypoint(?string $entrypointName = null, $where = Asset::FRONTEND)
    {
        /** @var Asset[] $assets */
        $assets = $this->viteLoader->load($entrypointName);
        foreach ($assets as $asset) {
            $asset->forLocation($where);
        }

        if (empty($assets)) {
            return;
        }

        \add_action(
            AssetManager::ACTION_SETUP,
            function (AssetManager $assetManager) use ($assets) {
                $assetManager->register(...$assets);
            }
        );
    }
}
