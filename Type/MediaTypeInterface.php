<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

interface MediaTypeInterface
{
    /**
     * Check whether the media type supports the provided form data.
     * This function is called in the postbind event of the form in order
     * to find all supporting types for the given data.
     * 
     * @param mixed $data the submitted form data
     * @return boolean
     */
    public function supports($data);
    /**
     * Called in the Form postBind event.
     * You can use this function to validate to data provided by the form
     * 
     * @param mixed $data the submitted form data
     * @return string $message the validation error message
     */
    public function validate($data);
    /**
     * Called in the Doctrine prePersist event.
     * 
     * @param MediaInterface $media
     */
    public function prePersist(MediaInterface $media);
    /**
     * Called in the Doctrine preUpdate event.
     * 
     * @param MediaInterface $media
     * @param array $changeSet
     */
    public function preUpdate(MediaInterface $media, array $changeSet);
    /**
     * Called in the Doctrine postRemove event
     * 
     * @param MediaInterface $media
     */
    public function postRemove(MediaInterface $media);
    /**
     * Called in the Doctrine postLoad event
     *
     * @param MediaInterface $media
     */
    public function postLoad(MediaInterface $media);
    /**
     * Unique name to identify the type
     * 
     * @return string
     */
    public function getName();
}
