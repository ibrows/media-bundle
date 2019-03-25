<?php

namespace Ibrows\MediaBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\FormBuilderInterface;

use Ibrows\MediaBundle\Form\Subscriber\MediaTypeGuessSubscriber;

class MediaUploadFormType extends AbstractMediaType
{
    protected $subscriber;

    public function __construct(MediaTypeGuessSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('data', 'file')
        ;

        $builder->addEventSubscriber($this->subscriber);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'type' => null,
            'error_bubbling' => false,
            'ignore_empty_update' => true,
        ));
    }

    /**
     *
     */
    public function getName()
    {
        return 'ibrows_media_upload';
    }

}
