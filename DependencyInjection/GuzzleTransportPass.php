<?php
namespace Corley\OpenTracingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class GuzzleTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('corley.auth.guzzle_handler_stack');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->addMethodCall('push', array(new Reference("opentracing.guzzle.middleware")));
        }
    }
}
