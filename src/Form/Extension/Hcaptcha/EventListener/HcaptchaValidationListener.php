<?php

namespace Rareloop\Lumberjack\Form\Extension\Hcaptcha\EventListener;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Rareloop\Lumberjack\Form\Extension\Hcaptcha\Form\Type\HcaptchaType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpClient\Psr18Client;

class HcaptchaValidationListener implements EventSubscriberInterface
{
    private string $secretKey;

    private ClientInterface $client;

    private string $errorMessage;

    public function __construct(string $secretKey, string $errorMessage, ?ClientInterface $client = null)
    {
        $this->client = $client ?? new Psr18Client();
        $this->secretKey = $secretKey;
        $this->errorMessage = $errorMessage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $catptcha = $this->getCaptcha($form);
        if (!$catptcha) {
            return;
        }

        $fieldName = $form->getConfig()->getOption('hcaptcha_field_name');
        if ($catptcha) {
            $fieldName = $catptcha->getName();
        }
        if (empty($fieldName)) {
            return;
        }

        $serverParams = new ServerParams();
        $postRequestSizeExceeded = $form->getConfig()->getMethod() === 'POST' && $serverParams->hasPostMaxSizeBeenExceeded();

        if ($form->isRoot() && $form->getConfig()->getOption('compound') && !$postRequestSizeExceeded) {
            $data = $event->getData();

            $hcaptchaToken = \is_string($data[$fieldName] ?? null) ? $data[$fieldName] : null;

            $request = new Request(
                'POST',
                'https://hcaptcha.com/siteverify',
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                \http_build_query([
                    'secret'   => $this->secretKey,
                    'response' => $hcaptchaToken,
                ], '', '&', PHP_QUERY_RFC1738)
            );

            try {
                $response = $this->client->sendRequest($request);
                $body = \json_decode((string) $response->getBody());
                if (isset($body->success) && !$body->success) {
                    $form->addError(new FormError($this->errorMessage));
                    return;
                }
            } catch (ClientExceptionInterface $e) {
                $form->addError(new FormError($e->getMessage()));
                return;
            }
        }
    }

    private function getCaptcha(FormInterface $form): ?FormInterface
    {
        foreach ($form->getIterator() as $child) {
            if ($child->getConfig()->getType()->getInnerType() instanceof HcaptchaType) {
                return $child;
            }
        }

        return null;
    }
}
