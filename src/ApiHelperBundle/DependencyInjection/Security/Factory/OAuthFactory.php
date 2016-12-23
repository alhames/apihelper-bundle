<?php

namespace ApiHelperBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * OAuthFactory
 */
class OAuthFactory extends AbstractFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        $parameterId = 'apihelper.security.options';
        $options = $container->hasParameter($parameterId) ? $container->getParameter($parameterId) : [];
        $options[$id] = $config;
        $container->setParameter($parameterId, $options);

        return parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'oauth';
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'apihelper.security.authentication.provider.'.$id;
        $container
            ->setDefinition($provider, new DefinitionDecorator('apihelper.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->replaceArgument(2, $id)
        ;

        return $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListenerId()
    {
        return 'apihelper.security.authentication.listener';
    }
}
