<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle;

use ApiHelperBundle\DependencyInjection\ApiHelperExtension;
use ApiHelperBundle\DependencyInjection\Security\Factory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class PgApiHelperBundle.
 */
class ApiHelperBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var $extension SecurityExtension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new Factory\OAuthFactory());
        $extension->addSecurityListenerFactory(new Factory\SafeFormLoginFactory());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new ApiHelperExtension();
    }
}
