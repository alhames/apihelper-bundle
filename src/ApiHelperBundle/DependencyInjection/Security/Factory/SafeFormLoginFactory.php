<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SafeFormLoginFactory.
 */
class SafeFormLoginFactory extends FormLoginFactory
{
    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'safe-form-login';
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        $this->createCaptchaListener($container, $id, $config);

        return parent::create($container, $id, $config, $userProviderId, $defaultEntryPointId);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $id
     * @param array            $config
     */
    public function createCaptchaListener(ContainerBuilder $container, $id, array $config)
    {
        $listenerId = 'apihelper.security.authentication.listener.captcha';
        $listener = new DefinitionDecorator($listenerId);
        $listener->replaceArgument(3, array_intersect_key($config, $this->options));
        $listener->replaceArgument(4, new Reference($this->getFailureHandlerId($id)));
        $listener->addTag('kernel.event_subscriber');

        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);
    }
}
