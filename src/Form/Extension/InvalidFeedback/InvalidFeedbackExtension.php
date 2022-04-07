<?php

namespace Rareloop\Lumberjack\Form\Extension\InvalidFeedback;

use Symfony\Component\Form\AbstractExtension;

class InvalidFeedbackExtension extends AbstractExtension
{
    protected function loadTypeExtensions(): array
    {
        return [
            new Type\FormTypeInvalidFeedbackExtension(),
        ];
    }
}
