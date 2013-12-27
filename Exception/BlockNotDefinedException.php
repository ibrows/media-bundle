<?php

namespace Ibrows\MediaBundle\Exception;

use Ibrows\MediaBundle\Model\MediaInterface;

class BlockNotDefinedException extends MediaException
{
    /**
     * @param MediaInterface $media
     * @param \Twig_Template $template
     */
    public function __construct(MediaInterface $media, $template)
    {
        $message = sprintf('Please provide a "%s" block inside template "%s" or override the template option if not already done so.', $media->getType(), $template);
        parent::__construct($message);
    }
}
