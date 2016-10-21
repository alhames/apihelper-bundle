<?php

namespace ApiHelperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
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

        $this->addServicesSection($rootNode);
        $this->addAuthenticationSection($rootNode);

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
                            ->scalarNode('class')->end()
                            ->scalarNode('client_id')->end()
                            ->scalarNode('client_secret')->end()
                            ->scalarNode('locale')->end()
                            ->scalarNode('redirect_uri')->end()
                            ->scalarNode('version')->end()
                            ->integerNode('timeout')->end()
                            ->floatNode('qps')->end()
                            ->arrayNode('scope')
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('display')->end()
                            ->scalarNode('access_token')->end()
                            ->scalarNode('refresh_token')->end()
                            ->arrayNode('options')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // services
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addAuthenticationSection(ArrayNodeDefinition $rootNode)
    {
        $defaultController = 'AppBundle\Controller\AuthenticationController';

        $rootNode
            ->children()
                ->arrayNode('authentication')
                    ->treatFalseLike(['enabled' => false])
                    ->treatTrueLike(['enabled' => true])
                    ->treatNullLike(['enabled' => true])
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('controller')
                            ->treatNullLike($defaultController)
                            ->cannotBeEmpty()
                            ->defaultValue($defaultController)
                        ->end()
                        ->arrayNode('services')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('routes')
                            ->children()
                                ->scalarNode('default')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('login')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // authentication
            ->end()
        ;
    }
}
