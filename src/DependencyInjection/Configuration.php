<?php
namespace Iltar\HttpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('iltar_http');

        $this->addRouterConfig($rootNode);

        return $treeBuilder;
    }

    /**
     * Resolves iltar_http.router
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addRouterConfig(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('router')
                    ->info('Configures the ParameterResolvingRouter')
                    ->canBeDisabled()
                    ->children()
                        ->booleanNode('entity_id_resolver')
                            ->info('Enable or disable the EntityIdResolver')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('mapped_getters')
                            ->info('Example: `App\User.username: getUsername`')
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
