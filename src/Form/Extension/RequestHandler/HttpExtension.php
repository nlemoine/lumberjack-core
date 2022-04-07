<?php

namespace Rareloop\Lumberjack\Form\Extension\RequestHandler;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\HttpFoundation\Type;

class HttpExtension extends AbstractExtension
{
    protected function loadTypeExtensions(): array
    {
        return [
            new Type\FormTypeHttpFoundationExtension(new HttpRequestHandler()),
        ];
    }
}
