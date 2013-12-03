<?php

namespace Ibrows\MediaBundle\Manager;

use Ibrows\MediaBundle\Exception\TypeAlreadyRegisteredException;

use Ibrows\MediaBundle\Type\MediaTypeInterface;
use Ibrows\MediaBundle\Exception\TypeNotRegisteredException;

class MediaTypeManager
{
    protected $registered_types = array();
    protected $enabled_types = array();
    
    public function __construct($enabled)
    {
        $enabled_types = array();
        foreach($enabled as $typename) {
            $enabled_types[$typename] = $typename;
        }
        
        $this->enabled_types = $enabled_types;
    }
    
    public function addMediaType($serviceId, MediaTypeInterface $type)
    {
        $name = $type->getName();
        if (array_key_exists($name, $this->registered_types)) {
            throw new TypeAlreadyRegisteredException($type);
        }
        
        $this->registered_types[$name] = $type;
        if (array_key_exists($name, $this->enabled_types)) {
            $this->enabled_types[$name] = $type;
        }
        return $this;
    }
    
    public function getMediaTypes()
    {
        return $this->enabled_types;
    }
    
    /**
     * 
     * @param unknown $value
     * @return array
     */
    public function getSupportingTypes($value) {
        $supporting = array();
        foreach ($this->enabled_types as $type) {
            if ($type->supports($value)) {
                $supporting[] = $type;
            }
        }
        
        return $supporting;
    }
    
    /**
     * 
     * @param unknown $value
     * @param array $types
     * @return AbstractMediaType
     */
    public function guessBestSupportingType($value, array $types = null)
    {
        //TODO:
    }
    
    public function getType($type)
    {
        if (!array_key_exists($type, $this->registered_types)) {
            throw new TypeNotRegisteredException($type);
        }
        return $this->registered_types[$type];
    }
}
