<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Security\Core\User;

use ApiHelperBundle\Security\Core\Exception\ServiceAccountNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ServiceAccountAwareUserProviderInterface.
 */
interface ServiceAccountAwareUserProviderInterface extends UserProviderInterface
{
    /**
     * @param string     $service
     * @param int|string $accountId
     *
     * @throws ServiceAccountNotFoundException if the user is not found
     *
     * @return UserInterface
     */
    public function loadUserByServiceAccount($service, $accountId);
}
