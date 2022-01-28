<?php

namespace Rareloop\Lumberjack\Assets;

use Inpsyde\Assets\Util\AssetHookResolver;
use Inpsyde\Assets\Asset;
use Inpsyde\WpContext;

class AjaxHookResolver extends AssetHookResolver
{
    /**
     * @var WpContext
     */
    public $context;

    /**
     * @param WpContext|null $context
     */
    public function __construct(?WpContext $context = null)
    {
        $this->context = $context ?? WpContext::determine();
    }

    /**
     * Resolving to the current location/page in WordPress all current hooks.
     *
     * @return string[]
     */
    public function resolve(): array
    {
        $assets = parent::resolve();
        if(!empty($assets)) {
            return $assets;
        }

        $isAjax = $this->context->isAjax();
        if(!$isAjax) {
            return $assets;
        }

        $assets[] = Asset::HOOK_FRONTEND;
        $assets[] = Asset::HOOK_BACKEND;
        $assets[] = Asset::HOOK_CUSTOMIZER_PREVIEW;
        return $assets;
    }

}
