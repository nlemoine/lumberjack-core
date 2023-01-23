<?php

namespace Rareloop\Lumberjack\Form\Extension\Hcaptcha\Type;

use Rareloop\Lumberjack\Form\Extension\Hcaptcha\EventListener\HcaptchaValidationListener;
use Rareloop\Lumberjack\Form\Extension\Hcaptcha\Form\Type\HcaptchaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FormTypeHcaptchaExtension extends AbstractTypeExtension
{
    private string $siteKey;

    private string $secretKey;

    private bool $defaultEnabled;

    private string $defaultFieldName;

    private string $errorMessage;

    public function __construct(
        string $siteKey,
        string $secretKey,
        bool $defaultEnabled,
        string $defaultFieldName,
        string $errorMessage
    ) {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->defaultEnabled = $defaultEnabled;
        $this->defaultFieldName = $defaultFieldName;
        $this->errorMessage = $errorMessage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventSubscriber(new HcaptchaValidationListener(
                $this->secretKey,
                $this->errorMessage
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $captcha = $this->getCaptcha($form);
        if ($captcha) {
            return;
        }

        if (!$view->parent && $options['compound'] && $options['hcaptcha_protection']) {
            $factory = $form->getConfig()->getFormFactory();

            $recaptcha_form = $factory->createNamed($options['hcaptcha_field_name'], HcaptchaType::class, null, [
                'mapped'      => false,
                'is_ajax'     => $options['hcaptcha_is_ajax'],
            ]);

            $view->children[$options['hcaptcha_field_name']] = $recaptcha_form->createView($view);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'hcaptcha_protection' => $this->defaultEnabled,
                'hcaptcha_field_name' => $this->defaultFieldName,
                'hcaptcha_site_key'   => $this->siteKey,
                'hcaptcha_is_ajax'    => false,
            ])
            ->setAllowedTypes('hcaptcha_protection', 'bool')
            ->setAllowedTypes('hcaptcha_is_ajax', 'bool')
            ->setAllowedTypes('hcaptcha_site_key', 'string')
            ->setAllowedTypes('hcaptcha_field_name', 'string')
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class,
        ];
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
