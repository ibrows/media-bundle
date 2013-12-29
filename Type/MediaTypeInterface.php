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
     * The returned value represents the confidence in supporting the data.
     * The higher the returned integer, the better the confidence.
     *
     * @param  mixed           $data the submitted form data
     * @return boolean|integer the confidence
     */
    public function supports($data);
    /**
     * Called in the Form postBind event.
     * You can use this function to validate to data provided by the form. You
     * can either return a string or a Symfony\Component\Form\FormError which
     * will get translated using the validators transation domain.
     *
     * @param  mixed $data the submitted form data
     * @return mixed $message the validation error
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
     * @param array          $changeSet
     */
    public function preUpdate(MediaInterface $media, array $changeSet);
    /**
     * Called in the Doctrine postRemove event
     *
     * @param MediaInterface $media
     */
    public function postRemove(MediaInterface $media);
    /**
     * Called in the Doctrine postUpdate event. Usually should do the same
     * as postLoad.
     *
     * @param MediaInterface $media
     */
    public function postUpdate(MediaInterface $media);
    /**
     * Called in the Doctrine postPersist event. Usually should do the same
     * as postLoad.
     *
     * @param MediaInterface $media
     */
    public function postPersist(MediaInterface $media);
    /**
     * Called in the Doctrine postLoad event. If you do load data (e.g. File)
     * you also should make sure that the data is reverted to database save values
     * before updating.
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
