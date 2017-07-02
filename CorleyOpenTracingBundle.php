<?php
namespace Corley\OpenTracingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Corley\OpenTracingBundle\DependencyInjection\GuzzleTransportPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CorleyOpenTracingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GuzzleTransportPass());
    }
}
