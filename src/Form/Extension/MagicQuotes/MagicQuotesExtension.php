<?php

namespace Rareloop\Lumberjack\Form\Extension\MagicQuotes;

use Symfony\Component\Form\AbstractExtension;

class MagicQuotesExtension extends AbstractExtension
{
    protected function loadTypeExtensions(): array
    {
        return [
            new Type\FormTypeMagicQuotesExtension(),
        ];
    }
}
