<?php

namespace Ibrows\MediaBundle\Form\Subscriber;

use Ibrows\MediaBundle\Exception\MissingDataClassException;

use Ibrows\MediaBundle\Manager\MediaTypeManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

class MediaTypeGuessSubscriber implements EventSubscriberInterface
{
    protected $manager;
    protected $translator;
    protected $oldData;
    
    public function __construct(MediaTypeManager $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
    }
    
    public static function getSubscribedEvents()
    {
        return array(
                FormEvents::PRE_SUBMIT => 'preSubmit',
                FormEvents::POST_SUBMIT => 'postSubmit'
        );
    }
    
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $form->getData();
        $this->oldData = $data ? $data->getData() : null;
    }

    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $media = $event->getData();
        if (!$media) {
            return;
        }
        
        if (is_array($media)) {
            throw new MissingDataClassException($form);
        }
        $value = $media->getData();
        $config = $form->getConfig();
        
        if ($config->getOption('ignore_empty_update') && $value === null) {
            $media->setData($this->oldData);
            return;
        }
        
        $typeName = $config->getOption('type');
        if ($typeName) {
            $type = $this->manager->getType($typeName);
            if (!$type->supports($value)) {
                $type = null;
            }
        } else {
            $type = $this->getBestMatchingType($value);
        }
        
        if ($type) {
            $media->setType($type->getName());
            $this->addFormError($form, $type->validate($value));
        } else {
            $this->addFormError($form, 'media.type.unsupported');
        }
    }
    
    protected function getBestMatchingType($value)
    {
        $types = $this->manager->getSupportingTypes($value);
        if(count($types)>1) {
            $type = $this->manager->guessBestSupportingType($value, $types);
        } else {
            $type = reset($types);
        }
    
        return $type;
    }
    
    protected function addFormError($form, $message)
    {
        if ($message instanceof FormError) {
            if (!$message->getMessage()) {
                $template = $message->getMessageTemplate();
                $params = $message->getMessageParameters();
                $message = new FormError($this->translator->trans($template, $params, 'validators'));
            }
            $form->addError($message);
        } else if ($message) {
            $message = $this->translator->trans($message, array(), 'validators');
            $error = new FormError($message);
            $form->addError($error);
        }
    }
}
