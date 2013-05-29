<?php

namespace Ibrows\MediaBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Ibrows\MediaBundle\Form\Subscriber\MediaTypeGuessSubscriber;
use Ibrows\MediaBundle\Manager\MediaTypeManager;

class MediaUploadFormType extends AbstractMediaType
{
    protected $subscriber;
    
    public function __construct(MediaTypeGuessSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('data', 'file')
        ;

        $builder->addEventSubscriber($this->subscriber);
    }
    
    /**
     * 
     */
    public function getName()
    {
        return 'ibrows_media_upload';
    }

}
