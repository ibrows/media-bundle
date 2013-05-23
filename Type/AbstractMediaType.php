<?php

namespace Ibrows\MediaBundle\Type;

abstract class AbstractMediaType implements MediaTypeInterface
{
    public function prePersist($data) 
    {
        return $data;
    }
    
    public function validate($data)
    {
        return null;
    }
    
    public function preUpdate($newdata, $olddata, $oldextra)
    {
        $this->postDelete($olddata, $oldextra);
        return $this->prePersist($newdata);
    }
    
    public function generateExtra($data)
    {
        return null;
    }
    
    public function generateUrl($data, $extra)
    {
        return null;
    }
    
    public function generateHtml($data, $extra)
    {
        return null;
    }
    
    public function postDelete($data, $extra)
    {
    }
}
