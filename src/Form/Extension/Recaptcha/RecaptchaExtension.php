<?php

namespace Rareloop\Lumberjack\Form\Extension\Recaptcha;

use Rareloop\Lumberjack\Form\Extension\Recaptcha\Type\FormTypeRecaptchaExtension;
use Symfony\Component\Form\AbstractExtension;

class RecaptchaExtension extends AbstractExtension
{
    private string $siteKey;

    private string $secretKey;

    private bool $defaultEnabled;

    private float $scoreThreshold;

    private string $defaultFieldName;

    private string $errorMessage;

    public function __construct(
        string $siteKey,
        string $secretKey,
        bool $defaultEnabled = false,
        ?float $scoreThreshold = null,
        ?string $defaultFieldName = null,
        ?string $errorMessage = null
    ) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->defaultEnabled = $defaultEnabled;
        $this->scoreThreshold = $scoreThreshold ?? 0.5;
        $this->defaultFieldName = $defaultFieldName ?? 'recaptcha_token';
        $this->errorMessage = $errorMessage ?? 'Error validating recaptcha';
    }

    protected function loadTypeExtensions()
    {
        return [
            new FormTypeRecaptchaExtension(
                $this->siteKey,
                $this->secretKey,
                $this->defaultEnabled,
                $this->scoreThreshold,
                $this->defaultFieldName,
                $this->errorMessage
            ),
        ];
    }
}
