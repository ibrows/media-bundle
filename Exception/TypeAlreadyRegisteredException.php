<?php

namespace Ibrows\MediaBundle\Exception;

use Ibrows\MediaBundle\Type\MediaTypeInterface;

class TypeAlreadyRegisteredException extends \Exception
{
    /**
     * @param string $message
     * @param string $code
     * @param string $previous
     */
    public function __construct(MediaTypeInterface $type)
    {
        $message = sprintf('A media type with name "%s" is already registered. Did you forget to overwrite the getName() method?', $type->getName());
        parent::__construct($message);
    }

}
