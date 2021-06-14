<?php

namespace Rareloop\Lumberjack\Providers;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('session', function () {
            return new Session($this->app->get('session.storage'));
        });

        $this->app->singleton('session.storage', function () {
            return new NativeSessionStorage($this->app->get('session.options'));
        });

        $this->app->singleton('session.options', function () {
            return [
                'name'            => $this->app->get('config')->get('session.name'),
                'cookie_lifetime' => $this->app->get('config')->get('session.lifetime'),
                'cookie_domain'   => $this->app->get('config')->get('session.domain'),
                'cookie_secure'   => \is_ssl(),
                'http_only'       => $this->app->get('config')->get('session.http_only'),
            ];
        });
    }
}
