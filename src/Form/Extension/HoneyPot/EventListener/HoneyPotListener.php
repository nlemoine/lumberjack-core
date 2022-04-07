<?php

namespace Rareloop\Lumberjack\Form\Extension\HoneyPot\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class HoneyPotListener implements EventSubscriberInterface
{
    /**
     * Error message.
     */
    private const ERROR_MESSAGE = 'Please fill a valid value';

    /**
     * @var string
     */
    private $fieldName;

    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        if (!$form->isRoot() || $form->getConfig()->getOption('compound') === null) {
            return;
        }

        $data = $event->getData();

        // Honeypot trap hit
        if (!isset($data[$this->fieldName]) || (string) $data[$this->fieldName] !== '') {
            $form->addError(new FormError(static::ERROR_MESSAGE));
        }

        // Remove honeypot
        if (\is_array($data)) {
            unset($data[$this->fieldName]);
        }

        $event->setData($data);
    }
}
