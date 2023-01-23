<?php

namespace Rareloop\Lumberjack\Form\Extension\Hcaptcha\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HcaptchaType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['site_key'] = $options['hcaptcha_site_key'];
        $view->vars['is_ajax'] = $options['is_ajax'];
        $view->vars['theme'] = $options['theme'];
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'hcaptcha';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'        => false,
            'mapped'       => false,
            'is_ajax'      => false,
            'site_key'     => '',
            'theme'        => 'light',
        ]);

        $resolver->setAllowedTypes('is_ajax', 'bool');
        $resolver->setAllowedTypes('site_key', 'string');
    }
}
