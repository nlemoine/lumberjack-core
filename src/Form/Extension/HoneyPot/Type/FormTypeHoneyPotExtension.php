<?php

namespace Rareloop\Lumberjack\Form\Extension\HoneyPot\Type;

use Rareloop\Lumberjack\Form\Extension\HoneyPot\EventListener\HoneyPotListener;
use RuntimeException;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FormTypeHoneyPotExtension extends AbstractTypeExtension
{
    /**
     * @var array<string, mixed>
     */
    private $defaults;

    public function __construct(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['honeypot'] !== true) {
            return;
        }

        $builder
            ->setAttribute('honeypot_factory', $builder->getFormFactory())
            ->addEventSubscriber(new HoneyPotListener($options['honeypot_field_name']))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if ($view->parent !== null || $options['honeypot'] !== true || $options['compound'] !== true) {
            return;
        }

        if ($form->has($options['honeypot_field_name'])) {
            throw new RuntimeException(\sprintf('Honeypot field "%s" is already used.', $options['honeypot_field_name']));
        }
        $factory = $form->getConfig()->getAttribute('honeypot_factory');

        if (!$factory instanceof FormFactoryInterface) {
            throw new RuntimeException('Invalid form factory to create a honeyput.');
        }

        $formOptions = $this->createViewOptions($options);

        $formView = $factory
            ->createNamed($options['honeypot_field_name'], $options['honeypot_field_type'], null, $formOptions)
            ->createView($view)
        ;

        $view->children[$options['honeypot_field_name']] = $formView;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'honeypot'             => $this->defaults['global'] ?? false,
                'honeypot_field_class' => $this->defaults['class'] ?? null,
                'honeypot_field_label' => $this->defaults['label'] ?? \__('Email address', 'regilait'),
                'honeypot_field_name'  => $this->defaults['field_name'] ?? 'email_address',
                'honeypot_field_type'  => $this->defaults['field_type'] ?? EmailType::class,
            ])
            ->setAllowedTypes('honeypot', 'bool')
            ->setAllowedTypes('honeypot_field_class', ['string', 'null'])
            ->setAllowedTypes('honeypot_field_name', 'string')
            ->setAllowedTypes('honeypot_field_type', 'string')
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class,
        ];
    }

    private function createViewOptions(array $options): array
    {
        $formOptions = [
            'mapped'   => false,
            'label'    => $options['honeypot_field_label'],
            'required' => false,
            'attr'     => [
                'autocomplete' => 'off',
                'tabindex'     => '-1',
                'aria-hidden'  => 'true',
            ],
            'error_bubbling' => true,
        ];

        if (!\array_key_exists('honeypot_field_class', $options) || $options['honeypot_field_class'] === null) {
            $formOptions['attr'] = \array_merge($formOptions['attr'], [
                'style' => 'display:none',
            ]);
            $formOptions['label_attr']['style'] = 'display:none';
        } else {
            $formOptions['attr'] = \array_merge($formOptions['attr'], [
                'class' => $options['honeypot_field_class'],
            ]);
            $formOptions['label_attr']['class'] = $options['honeypot_field_class'];
        }

        return $formOptions;
    }
}
