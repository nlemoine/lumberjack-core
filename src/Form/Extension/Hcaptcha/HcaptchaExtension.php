<?php

namespace Rareloop\Lumberjack\Form\Extension\Hcaptcha;

use Rareloop\Lumberjack\Form\Extension\Hcaptcha\Type\FormTypeHcaptchaExtension;
use Symfony\Component\Form\AbstractExtension;

class HcaptchaExtension extends AbstractExtension
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
        ?string $defaultFieldName = null,
        ?string $errorMessage = null
    ) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->defaultEnabled = $defaultEnabled;
        $this->defaultFieldName = $defaultFieldName ?? 'hcaptcha_token';
        $this->errorMessage = $errorMessage ?? 'Error validating recaptcha';
    }

    protected function loadTypeExtensions(): array
    {
        return [
            new FormTypeHcaptchaExtension(
                $this->siteKey,
                $this->secretKey,
                $this->defaultEnabled,
                $this->defaultFieldName,
                $this->errorMessage
            ),
        ];
    }
}
