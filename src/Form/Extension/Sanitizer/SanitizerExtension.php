<?php

namespace Rareloop\Lumberjack\Form\Extension\Sanitizer;

use Rareloop\Lumberjack\Form\Extension\Sanitizer\Type\FormTypeSanitizerExtension;
use Symfony\Component\Form\AbstractExtension;

class SanitizerExtension extends AbstractExtension
{
    protected function loadTypeExtensions(): array
    {
        return [
            new FormTypeSanitizerExtension(),
        ];
    }
}
