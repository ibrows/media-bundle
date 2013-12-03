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
                Events::preRemove,
                Events::postRemove,
                Events::postLoad
        );
    }
    
    /**
     * Post load
     * 
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $media = $this->getObject($args);
        
        if ($media instanceof MediaInterface) {
            $type = $this->manager->getType($media->getType());
            $type->postLoad($media);
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
            $type = $this->manager->getType($media->getType());
            $type->prePersist($media);
        }
    }
    
    /**
     * Transform the media files
     * 
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $media = $this->getObject($args);
        $em = $args->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $uow = $em->getUnitOfWork();
        
        if ($media instanceof MediaInterface && $args->hasChangedField('data')) {
            $type = $this->manager->getType($media->getType());
            $type->preUpdate($media, $args->getEntityChangeSet());
            
            $mediaMeta = $em->getClassMetadata(get_class($media));
            $uow->recomputeSingleEntityChangeSet($mediaMeta, $media);
        }
    }

    /**
     * In order to make sure we always have a fully loaded entity
     * in the postRemove event we need to refresh it
     * 
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $media = $this->getObject($args);
        $em = $args->getEntityManager();
        
        if ($media instanceof MediaInterface) {
            $em->refresh($args->getEntity());
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
            $type->postRemove($media);
        }
    }
    
    /**
     * @param LifecycleEventArgs $args
     * @return mixed
     */
    protected function getObject(LifecycleEventArgs $args)
    {
        return $args->getEntity();
    }
}
