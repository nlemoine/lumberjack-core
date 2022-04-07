<?php

namespace Rareloop\Lumberjack\Form\Extension\Sanitizer\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SanitizerListener implements EventSubscriberInterface
{
    /**
     * Setup the list of events for the form.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onSubmission',
        ];
    }

    /**
     * Handle sanitization when the form is submitted.
     */
    public function onSubmission(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isRoot()) {
            $data = $event->getData();
            $fields = $form->all();

            $event->setData($this->sanitizeData($data, $fields));
        }
    }

    /**
     * Generate the sanitized array of submitted data.
     *
     * @param array $data   data of the form
     * @param array $fields fields list
     *
     * @return array
     */
    private function sanitizeData($data, $fields)
    {
        $sanitized = [];

        if (!\is_array($data)) {
            return $sanitized;
        }

        foreach ($fields as $field) {
            if (!\array_key_exists($field->getName(), $data)) {
                continue;
            }

            $type = $field->getConfig()->getType()->getInnerType();
            $config = $field->getConfig();

            if ($config->hasOption('sanitizer') && $config->getOption('sanitizer') === false) {
                $sanitized[$field->getName()] = $data[$field->getName()];
            } else {
                switch (\get_class($type)) {
                    case EmailType::class:
                        $sanitized[$field->getName()] = \sanitize_email($data[$field->getName()]);
                        // no break
                    case TextareaType::class:
                        $sanitized[$field->getName()] = \sanitize_textarea_field($data[$field->getName()]);

                        break;

                    case FileType::class:
                        $sanitized[$field->getName()] = $data[$field->getName()];

                        break;

                    default:
                        $sanitized[$field->getName()] = $this->clean($data[$field->getName()]);

                        break;
                }
            }
        }

        return \array_merge($data, $sanitized);
    }

    /**
     * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
     * Non scalar values are ignored.
     *
     * @param array|string $var variable to clean
     *
     * @return array|string
     */
    private function clean($var)
    {
        if (\is_array($var)) {
            return \array_map([self::class, 'clean'], $var);
        }

        return \sanitize_text_field($var);
    }
}
