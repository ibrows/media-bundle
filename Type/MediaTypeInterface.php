<?php

namespace Ibrows\MediaBundle\Type;

interface MediaTypeInterface
{
    /**
     * Check whether the media type supports the provided form data.
     * This function is called in the postbind event of the form in order
     * to find all supporting types for the given data.
     * 
     * @param mixed $data
     * @return boolean
     */
    public function supports($data);
    /**
     * Called in the Doctrine prePersist event.
     * 
     * @param unknown $data
     * @return string the data to be set in the media entity
     */
    public function prePersist($data);
    /**
     * Called in the Form postBind event.
     * You can use this function to validate to data provided by the form
     * 
     * @param unknown $data
     * @return string $message the validation error message
     */
    public function validate($data);
    /**
     * Called in the Doctrine preUpdate event.
     * 
     * @param unknown $newdata
     * @param unknown $olddata
     * @param array $oldextra
     * @return $data
     */
    public function preUpdate($newdata, $olddata, $oldextra);
    /**
     * Called in the Doctrine prePersist event.
     * 
     * @param unknown $data
     * @return array $extra
     */
    public function generateExtra($data);
    /**
     * Called in the Doctrine prePersist event.
     * 
     * @param unknown $data
     * @param unknown $extra
     * @return string $url
     */
    public function generateUrl($data, $extra);
    /**
     * Called in the Doctrine prePersist event.
     * 
     * @param unknown $data
     * @return string $html
     */
    public function generateHtml($data, $extra);
    /**
     * Called in the Doctrine postDelete event
     * 
     * @param unknown $data
     */
    public function postDelete($data, $extra);
    /**
     * Unique name to identify the type
     * 
     * @return string
     */
    public function getName();
}
