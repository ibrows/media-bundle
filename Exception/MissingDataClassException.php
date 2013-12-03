<?php

namespace Ibrows\MediaBundle\Exception;

use Symfony\Component\Form\FormInterface;

class MissingDataClassException extends MediaException
{
    /**
     * @param string $message
     * @param string $code
     * @param string $previous
     */
    public function __construct(FormInterface $form)
    {
        $message = sprintf('Please provide the "data_class" option for form  "%s"', $form->getName());
        parent::__construct($message);
    }
}
