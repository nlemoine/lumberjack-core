<?php

namespace Rareloop\Lumberjack\Form\Extension\InvalidFeedback\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeInvalidFeedbackExtension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['invalid_feedback']) {
            $view->vars['invalid_feedback'] = $options['invalid_feedback'];
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('invalid_feedback', null);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
