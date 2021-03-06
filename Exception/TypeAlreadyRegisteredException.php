<?php

namespace Ibrows\MediaBundle\Exception;

use Ibrows\MediaBundle\Type\MediaTypeInterface;

class TypeAlreadyRegisteredException extends MediaException
{
    /**
     * @param string $message
     * @param string $code
     * @param string $previous
     */
    public function __construct(MediaTypeInterface $type)
    {
        $message = sprintf('A media type with name "%s" is already registered. Did you forget to overwrite the getName() method in "%s"?', $type->getName(), get_class($type));
        parent::__construct($message);
    }

}
