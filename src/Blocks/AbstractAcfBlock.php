<?php

namespace Rareloop\Lumberjack\Blocks;

use Timber\Timber;

abstract class AbstractAcfBlock
{
    protected $block;

    protected $content;

    protected $isPreview;

    protected $postId;

    public function __construct($block, $content = '', $isPreview = false, $postId = null)
    {
        $this->block = $block;
        $this->content = $content;
        $this->isPreview = $isPreview;
        $this->postId = $postId;
    }

    public function context(): array
    {
        return [];
    }

    public function render()
    {
        $data = $this->context();

        $templates = [
            'blocks/acf/' . static::getName() . '.html.twig',
        ];
        if (\is_admin()) {
            \array_unshift($templates, 'blocks/acf/' . static::getName() . '-admin.html.twig');
        }
        $data['block'] = $this->block;
        $data['is_preview'] = $this->isPreview;

        echo Timber::compile($templates, $data);
    }

    abstract public static function getBlockConfig(): array;

    public static function getName(): string
    {
        $config = static::getBlockConfig();

        return $config['name'];
    }

    public static function register()
    {
        if (!\function_exists('acf_register_block_type')) {
            return;
        }

        \acf_register_block_type(\array_merge(static::getBlockConfig(), [
            'render_callback' => function (array $block, string $content, bool $is_preview, int $post_id) {
                $block = new static($block, $content, $is_preview, $post_id);
                return $block->render();
            },
        ]));
    }
}
