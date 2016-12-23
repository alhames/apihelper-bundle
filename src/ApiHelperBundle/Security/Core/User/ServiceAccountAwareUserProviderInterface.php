<?php

namespace ApiHelperBundle\Security\Core\User;

use ApiHelperBundle\Security\Core\Exception\ServiceAccountNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ServiceAccountAwareUserProviderInterface
 *
 * @package ApiHelperBundle\Security\Core\User
 */
interface ServiceAccountAwareUserProviderInterface extends UserProviderInterface
{
    /**
     * @param string     $service
     * @param int|string $accountId
     *
     * @return UserInterface
     *
     * @throws ServiceAccountNotFoundException if the user is not found
     */
    public function loadUserByServiceAccount($service, $accountId);
}
