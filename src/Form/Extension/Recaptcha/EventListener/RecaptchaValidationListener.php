<?php

namespace App\Form\Extension\Recaptcha\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Util\ServerParams;
use ReCaptcha\ReCaptcha;

class RecaptchaValidationListener implements EventSubscriberInterface
{
    private string $secretKey;
    private string $fieldName;
    private float $scoreThreshold;
    private string $errorMessage;

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function __construct(string $secretKey, float $scoreThreshold, string $fieldName, string $errorMessage)
    {
        $this->fieldName = $fieldName;
        $this->secretKey = $secretKey;
        $this->scoreThreshold = $scoreThreshold;
        $this->errorMessage = $errorMessage;
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $serverParams = new ServerParams();
        $postRequestSizeExceeded = 'POST' === $form->getConfig()->getMethod() && $serverParams->hasPostMaxSizeBeenExceeded();

        if ($form->isRoot() && $form->getConfig()->getOption('compound') && !$postRequestSizeExceeded) {
            $data = $event->getData();

            $recaptchaToken = \is_string($data[$this->fieldName] ?? null) ? $data[$this->fieldName] : null;
            $recaptcha = new ReCaptcha($this->secretKey);
            $action_name = $form->getName() ?: \get_class($form->getConfig()->getType()->getInnerType());

            $request = Request::createFromGlobals();
            $remote_ip = $request->getClientIp();
            $hostname = $request->getHost();

            $response = $recaptcha
                ->setExpectedHostname($hostname)
                ->setExpectedAction($action_name)
                ->setScoreThreshold($this->scoreThreshold)
                ->verify($recaptchaToken, $remote_ip)
            ;

            if (!$response->isSuccess()) {
                $errorMessage = \sprintf('%s (%s)', $this->errorMessage, \implode(', ', $response->getErrorCodes()));
                $form->addError(new FormError($errorMessage, $errorMessage, [], null, $recaptcha));
            }

            if (\is_array($data)) {
                unset($data[$this->fieldName]);
                $event->setData($data);
            }
        }
    }
}
