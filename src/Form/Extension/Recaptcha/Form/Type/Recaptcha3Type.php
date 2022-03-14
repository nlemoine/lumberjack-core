<?php

namespace Rareloop\Lumberjack\Form\Extension\Recaptcha\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class Recaptcha3Type extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['action_name'] = $options['action_name'];
        $view->vars['site_key'] = $options['recaptcha_site_key'];
        $view->vars['is_ajax'] = $options['is_ajax'];
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'recaptcha3';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label'        => false,
            'mapped'       => false,
            'is_ajax'      => false,
            'site_key'     => '',
            'action_name'  => '',
        ]);

        $resolver->setAllowedTypes('is_ajax', 'bool');
        $resolver->setAllowedTypes('action_name', 'string');
        $resolver->setAllowedTypes('site_key', 'string');
    }
}
