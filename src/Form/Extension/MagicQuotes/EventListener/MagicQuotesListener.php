<?php

namespace Rareloop\Lumberjack\Form\Extension\MagicQuotes\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class MagicQuotesListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isRoot()) {
            $event->setData(\wp_unslash($event->getData()));
        }
    }
}
