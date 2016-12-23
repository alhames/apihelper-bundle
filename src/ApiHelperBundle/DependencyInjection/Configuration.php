<?php

namespace ApiHelperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('apihelper');
        $rootNode
            ->children()
                ->scalarNode('controller')->end()
            ->end()
        ;
        $this->addServicesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addServicesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('services')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->children()

                            ->scalarNode('security_connect')
                                ->defaultTrue()
                                ->treatNullLike(true)
                            ->end()
                            ->arrayNode('security_login')
                                ->treatNullLike([null])
                                ->treatTrueLike([null])
                                ->treatFalseLike([])
                                ->defaultValue([null])
                                ->prototype('scalar')->end()
                            ->end()

                            ->scalarNode('service')->end()
                            ->scalarNode('client_class')->end()
                            ->scalarNode('account_class')->end()
                            ->scalarNode('client_id')->end()
                            ->scalarNode('client_secret')->end()
                            ->scalarNode('locale')
                                ->defaultValue('en')
                                ->treatNullLike('en')
                            ->end()
                            ->scalarNode('redirect_uri')->end()
                            ->scalarNode('version')->end()
                            ->floatNode('timeout')
                                ->min(0)
                                ->defaultValue(0)
                                ->treatNullLike(0)
                                ->treatFalseLike(0)
                            ->end()
                            ->floatNode('qps')
                                ->defaultValue(10)
                                ->treatNullLike(10)
                            ->end()
                            ->arrayNode('scope')
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('access_token')->end()
                            ->arrayNode('options')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // services
            ->end()
        ;
    }
}
