<?php

namespace Rareloop\Lumberjack\Providers;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('session', function () {
            return new Session($this->get('session.storage'));
        });

        $this->app->singleton('session.storage', function () {
            return new NativeSessionStorage($this->get('session.options'));
        });

        $this->app->singleton('session.options', function () {
            return [
                'name'            => $this->getConfig('session.name'),
                'cookie_lifetime' => $this->getConfig('session.lifetime'),
                'cookie_domain'   => $this->getConfig('session.domain'),
                'cookie_secure'   => \is_ssl(),
                'http_only'       => $this->getConfig('session.http_only'),
            ];
        });
    }
}
