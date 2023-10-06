<?php

namespace Rareloop\Lumberjack\Providers;

use Rareloop\Lumberjack\Mailer\Transport\WordPressTransport;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;
use Twig\Environment;

class MailerServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->set(BodyRendererInterface::class, \DI\create(BodyRenderer::class)->constructor(\DI\get(Environment::class)));
        $this->app->set('mailer.envelope_listener', \DI\create(EnvelopeListener::class)->constructor(\DI\get('mailer.sender')));
        $this->app->set('mailer.message_listener', \DI\create(MessageListener::class)->constructor(null, \DI\get(BodyRendererInterface::class)));
        $this->app->set(
            'mailer.dispatcher',
            \DI\create(EventDispatcher::class)
                ->method('addSubscriber', \DI\get('mailer.message_listener'))
                ->method('addSubscriber', \DI\get('mailer.envelope_listener'))
        );
        $this->app->set(TransportInterface::class, \DI\create(WordPressTransport::class)->constructor(\DI\get('mailer.dispatcher')));

        $this->app->set(MailerInterface::class, \DI\create(Mailer::class)->constructor(
            \DI\get(TransportInterface::class),
            null,
            \DI\get('mailer.dispatcher')
        ));

        $this->app->singleton('mailer.sender', function () {
            $sender = new Address('noreply@' . \str_replace('www.', '', \parse_url(\home_url(), PHP_URL_HOST)), \get_bloginfo('name'));
            return $this->getConfig('mailer.sender') ?? $sender;
        });
    }
}
