<?php

namespace Ibrows\MediaBundle\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MediaTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if(!$container->hasDefinition('ibrows_media.type.manager')) {
            return;
        }
        
        $definition = $container->getDefinition(
                'ibrows_media.type.manager'
        );
        
        $taggedServices = $container->findTaggedServiceIds(
                'ibrows_media.type'
        );
        
        foreach($taggedServices as $id => $attributes){
            $definition->addMethodCall(
                    'addMediaType',
                    array($id, new Reference($id))
            );
        }
    }

}
