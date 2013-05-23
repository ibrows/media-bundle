<?php

namespace Ibrows\MediaBundle\Doctrine\Subscriber;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

use Ibrows\MediaBundle\Manager\MediaTypeManager;
use Ibrows\MediaBundle\Model\MediaInterface;
use Ibrows\MediaBundle\Type\MediaTypeInterface;

class MediaTypeSubscriber implements EventSubscriber
{
    /**
     * @var MediaTypeManager
     */
    protected $manager;

    public function __construct(MediaTypeManager $manager)
    {
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return array(
                Events::prePersist,
                Events::preUpdate,
                Events::postRemove
        );
    }
    
    public function postLoad(LifecycleEventArgs $args)
    {
        $media = $this->getObject($args);
        
        if ($media instanceof MediaInterface && $media->getType() == 'uploadedimage') {
            $data = $media->getData();
            if (file_exists($data)) {
                $media->setData(new UploadedFile($data, ''));
            } else {
                $media->setData(null);
            }
        }
    }

    /**
     * Transform the media files
     *
     * @param \Doctrine\Common\EventArgs $args The event arguments.
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $media = $this->getObject($args);
        $em = $args->getEntityManager();
        
        if ($media instanceof MediaInterface) {
            $value = $media->getData();
            $type = $this->manager->getType($media->getType());
            $value = $type->prePersist($media->getData());
        
            $extra = $type->generateExtra($value);
            $media->setData($value);
            $media->setExtra($extra);
            $media->setUrl($type->generateUrl($value, $extra));
            $media->setHtml($type->generateHtml($value, $extra));
        }
    }
    
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $media = $this->getObject($args);
        $em = $args->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $uow = $em->getUnitOfWork();
        
        if ($media instanceof MediaInterface && $args->hasChangedField('data')) {
            $mediaMeta = $em->getClassMetadata(get_class($media));
            $type = $this->manager->getType($media->getType());
            $value = $args->getNewValue('data');
            $oldvalue = $args->getOldValue('data');
            
            $value = $type->preUpdate($value, $oldvalue, $media->getExtra());
            $extra = $type->generateExtra($value);
            $url = $type->generateUrl($value, $extra);
            $html = $type->generateHtml($value, $extra);
            
            $media->setData($value);
            $media->setExtra($extra);
            $media->setUrl($url);
            $media->setHtml($html);
            
            $uow->recomputeSingleEntityChangeSet($mediaMeta, $media);
        }
    }

    /**
     * Removes the file if necessary.
     *
     * @param EventArgs $args The event arguments.
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $media = $this->getObject($args);
        
        if ($media instanceof MediaInterface) {
            $type = $this->manager->getType($media->getType());
            
            $type->postDelete($media->getData(), $media->getExtra());
        }
    }
    
    protected function getObject(LifecycleEventArgs $args)
    {
        return $args->getEntity();
    }
}
