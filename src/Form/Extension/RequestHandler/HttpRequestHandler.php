<?php

namespace Rareloop\Lumberjack\Form\Extension\RequestHandler;

use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpRequestHandler extends HttpFoundationRequestHandler
{
    public function handleRequest(FormInterface $form, $request = null): void
    {
        $request = Request::createFromGlobals();
        parent::handleRequest($form, $request);
    }
}
