<?php

namespace Ibrows\MediaBundle;

use Ibrows\MediaBundle\Compiler\MediaTypeCompilerPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IbrowsMediaBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MediaTypeCompilerPass());
    }

}
