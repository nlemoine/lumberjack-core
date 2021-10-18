<?php

namespace Rareloop\Lumberjack\Form\Extension\InvalidFeedback;

use Symfony\Component\Form\AbstractExtension;

class InvalidFeedbackExtension extends AbstractExtension
{
    protected function loadTypeExtensions()
    {
        return [
            new Type\FormTypeInvalidFeedbackExtension(),
        ];
    }
}
