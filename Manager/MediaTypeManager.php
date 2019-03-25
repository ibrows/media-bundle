<?php

namespace Ibrows\MediaBundle\Manager;

use Ibrows\MediaBundle\Exception\TypeAlreadyRegisteredException;

use Ibrows\MediaBundle\Type\AbstractMediaType;
use Ibrows\MediaBundle\Type\MediaTypeInterface;
use Ibrows\MediaBundle\Exception\TypeNotRegisteredException;

class MediaTypeManager
{
    protected $registered_types = array();
    protected $enabled_types = array();

    public function __construct($enabled)
    {
        $enabled_types = array();
        foreach ($enabled as $typename) {
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

    public function getAllMediatypes()
    {
        return $this->registered_types;
    }

    /**
     *
     * @param  mixed $value
     * @return array
     */
    public function getSupportingTypes($value, array $enabled = array())
    {
        $supporting = array();
        if (empty($enabled)) {
            $enabled = $this->enabled_types;
        }

        foreach ($enabled as $type) {
            $mediaType = $this->getType($type);
            $confidence = (int) $mediaType->supports($value);
            if ($confidence > 0) {
                $supporting[$confidence] = $mediaType;
            }
        }

        return $supporting;
    }

    /**
     *
     * @param  mixed           $value
     * @param  array             $types
     * @return AbstractMediaType
     */
    public function guessBestSupportingType($value, array $types = array())
    {
        ksort($types);

        return end($types);
    }

    public function getType($type)
    {
        if ($type instanceof MediaTypeInterface) {
            return $type;
        }

        if (!array_key_exists($type, $this->registered_types)) {
            throw new TypeNotRegisteredException($type);
        }

        return $this->registered_types[$type];
    }
}
