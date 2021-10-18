<?php

namespace Rareloop\Lumberjack\Form\Extension\HoneyPot;

use Rareloop\Lumberjack\Form\Extension\HoneyPot\Type\FormTypeHoneyPotExtension;
use Symfony\Component\Form\AbstractExtension;

class HoneyPotExtension extends AbstractExtension
{
    private $defaults;

    public function __construct(array $defaults)
    {
        $this->defaults = $defaults;
    }

    protected function loadTypeExtensions()
    {
        return [
            new FormTypeHoneyPotExtension($this->defaults),
        ];
    }
}
