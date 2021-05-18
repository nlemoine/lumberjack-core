<?php

namespace Rareloop\Lumberjack\Http\Responses;

use Laminas\Diactoros\Response\RedirectResponse as DiactorosRedirectResponse;
use Rareloop\Lumberjack\Helpers;

class RedirectResponse extends DiactorosRedirectResponse
{
    public function with($key = null, $value = null)
    {
        if (is_array($key)) {
            Helpers::app('session')->flash($key);
        } else {
            Helpers::app('session')->flash($key, $value);
        }

        return $this;
    }
}
