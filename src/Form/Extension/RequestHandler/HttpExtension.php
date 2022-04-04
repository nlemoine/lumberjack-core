<?php

namespace Rareloop\Lumberjack\Form\Extension\RequestHandler;

use Symfony\Component\Form\AbstractExtension;
use Symfony\Component\Form\Extension\HttpFoundation\Type;

class HttpExtension extends AbstractExtension
{
    protected function loadTypeExtensions()
    {
        return [
            new Type\FormTypeHttpFoundationExtension(new HttpRequestHandler()),
        ];
    }
}
