<?php

namespace Rareloop\Lumberjack\Form\Extension\Sanitizer\Type;

use Rareloop\Lumberjack\Form\Extension\Sanitizer\EventListener\SanitizerListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeSanitizerExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['sanitizer'] === false) {
            return;
        }

        $builder->addEventSubscriber(new SanitizerListener());
    }

    /**
     * Setup form sanitization enabled by default.
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'sanitizer' => true,
            ]
        );
    }


    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
