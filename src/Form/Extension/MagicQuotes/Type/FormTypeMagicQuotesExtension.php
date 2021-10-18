<?php

namespace Rareloop\Lumberjack\Form\Extension\MagicQuotes\Type;

use Rareloop\Lumberjack\Form\Extension\MagicQuotes\EventListener\MagicQuotesListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class FormTypeMagicQuotesExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber(new MagicQuotesListener())
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
