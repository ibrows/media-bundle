<?php

namespace Ibrows\MediaBundle\Exception;

use Ibrows\MediaBundle\Type\MediaTypeInterface;

class TypeNotRegisteredException extends MediaException
{
    /**
     * @param string $message
     * @param string $code
     * @param string $previous
     */
    public function __construct($typeName)
    {
        $message = sprintf('A media type with name "%s" is not registered. Did you forget to set the "ibrows_media.type" tag in the service definition of "%s"?', $typeName, $typeName);
        parent::__construct($message);
    }

}
