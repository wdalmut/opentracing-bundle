<?php
namespace Corley\OpenTracingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('corley_open_tracing');

        $rootNode
            ->children()
                ->scalarNode("app_name")->defaultValue("app")->end()
                ->scalarNode("zipkin")->defaultValue("http://localhost:9411")->end()
                ->scalarNode("send_traces")->defaultValue(true)->end()
            ->end();

        return $treeBuilder;
    }
}
