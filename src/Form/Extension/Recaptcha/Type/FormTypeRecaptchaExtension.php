<?php

namespace App\Form\Extension\Recaptcha\Type;

use App\Form\Extension\Recaptcha\EventListener\RecaptchaValidationListener;
use App\Form\Extension\Recaptcha\Form\Type\Recaptcha3Type;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FormTypeRecaptchaExtension extends AbstractTypeExtension
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
        bool $defaultEnabled,
        float $scoreThreshold,
        string $defaultFieldName,
        string $errorMessage
    )
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
        $this->defaultEnabled = $defaultEnabled;
        $this->scoreThreshold = $scoreThreshold;
        $this->defaultFieldName = $defaultFieldName;
        $this->errorMessage = $errorMessage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['recaptcha_protection']) {
            return;
        }

        $builder
            ->addEventSubscriber(new RecaptchaValidationListener(
                $this->secretKey,
                $this->scoreThreshold,
                $options['recaptcha_field_name'],
                $this->errorMessage
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['recaptcha_protection'] && !$view->parent && $options['compound']) {
            $factory = $form->getConfig()->getFormFactory();

            $action_name = $form->getName() ?: \get_class($form->getConfig()->getType()->getInnerType());

            $recaptcha_form = $factory->createNamed($options['recaptcha_field_name'], Recaptcha3Type::class, null, [
                'action_name' => $action_name,
                'mapped'      => false,
                'is_ajax' => $options['recaptcha_is_ajax'],
            ]);

            $view->children[$options['recaptcha_field_name']] = $recaptcha_form->createView($view);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'recaptcha_protection' => $this->defaultEnabled,
                'recaptcha_field_name' => $this->defaultFieldName,
                'recaptcha_site_key'   => $this->siteKey,
                'recaptcha_is_ajax'    => false,
            ])
            ->setAllowedTypes('recaptcha_protection', 'bool')
            ->setAllowedTypes('recaptcha_is_ajax', 'bool')
            ->setAllowedTypes('recaptcha_site_key', 'string')
            ->setAllowedTypes('recaptcha_field_name', 'string')
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class,
        ];
    }

}
