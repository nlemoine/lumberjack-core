<?php

namespace Rareloop\Lumberjack\Form\Type;

use App\Form\DataTransformer\FileToUploadedFileTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileUploadType extends AbstractType
{
    private $transformer;

    public function __construct()
    {
        // $this->transformer = new FileToUploadedFileTransformer();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'The selected issue does not exist',
        ]);
    }

    public function getParent()
    {
        return FileType::class;
    }
}
