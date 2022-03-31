<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Mailer\Transport\WordPressTransport;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\TransportInterface;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->set(BodyRendererInterface::class, \DI\create(BodyRenderer::class)->constructor(\DI\get(Environment::class)));
        $this->app->set('mailer.listener', \DI\create(MessageListener::class)->constructor(null, \DI\get(BodyRendererInterface::class)));
        $this->app->set('mailer.dispatcher', \DI\create(EventDispatcher::class)->method('addSubscriber', \DI\get('mailer.listener')));
        $this->app->set(TransportInterface::class, \DI\create(WordPressTransport::class)->constructor(\DI\get('mailer.dispatcher')));

        $this->app->set(MailerInterface::class, \DI\create(Mailer::class)->constructor(
            \DI\get(TransportInterface::class),
            null,
            \DI\get('mailer.dispatcher')
        ));

        // $this->app->singleton('app.mailer.default_to', function () {
        //     return new Address(\get_option('admin_email'));
        // });

        // $this->app->singleton('app.mailer.default_from', function () {
        //     return new Address('ne-pas-repondre@regilait.com', 'Régilait');
        // });

        // $this->app->singleton('app.mailer.subject_prefix', function () {
        //     return '[] ';
        // });

        // $this->app->singleton('app.mailer.renderer', function () {
        //     return new BodyRenderer($this->app->get('twig'));
        // });
    }
}
