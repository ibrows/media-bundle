<?php

namespace Ibrows\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractMediaType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'type' => null,
            'error_bubbling' => false,
            'ignore_empty_update' => false,
        ));
    }
}
