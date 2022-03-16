<?php

namespace Rareloop\Lumberjack\Providers;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('session.options', function () {
            return [
                'name'            => $this->getConfig('session.name'),
                'cookie_lifetime' => $this->getConfig('session.lifetime'),
                'cookie_domain'   => $this->getConfig('session.domain'),
                'cookie_secure'   => \is_ssl(),
                'http_only'       => $this->getConfig('session.http_only'),
            ];
        });

        $this->app->singleton(SessionStorageInterface::class, \DI\create(NativeSessionStorage::class)->constructor(\DI\get('session.options'))->lazy());
        $this->app->singleton(SessionInterface::class, \DI\create(Session::class)->constructor(\DI\get(SessionStorageInterface::class))->lazy());
        $this->app->singleton('session', $this->app->get(SessionInterface::class));
    }
}
