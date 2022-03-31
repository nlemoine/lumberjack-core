<?php

namespace Rareloop\Lumberjack\Form;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Intl\Countries;

class FormDataTransformer
{
    public static function transform(FormInterface $form)
    {
        // Create view (to display form data in emails)
        $form_data = [];
        foreach ($form as $k => $field) {
            $field_config = $field->getConfig();
            $field_type = $field_config->getType()->getInnerType();
            $field_options = $field_config->getOptions();
            $field_data = $field->getData();
            $field_class = \get_class($field_type);

            // Remove files
            if ($field_class === Type\FileType::class) {
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $field_data instanceof UploadedFile ? $field->getData()->getClientOriginalName() : null,
                ];
            } elseif ($field_class === Type\DateType::class) {
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $field_data instanceof \DateTime ? $field_data->format('d-m-Y') : null,
                ];
            } elseif ($field_class === Type\TimeType::class) {
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $field_data instanceof \DateTime ? $field_data->format('H:i') : null,
                ];
            } elseif ($field_class === Type\CheckboxType::class) {
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $field_data ? '✅' : '❌',
                ];
            } elseif ($field_class === Type\CountryType::class) {
                try {
                    $country = Countries::getName($field_data);
                } catch (\Exception $e) {
                    $country = $field_data;
                }
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $country,
                ];
            } else {
                $form_data[$field->getName()] = [
                    'label' => $field_options['label'] ?? null,
                    'value' => $field_data,
                ];
            }
        }

        // Remove empty label & value
        return \array_filter($form_data, function ($field) {
            return !empty($field['label']);
        });
    }
}
