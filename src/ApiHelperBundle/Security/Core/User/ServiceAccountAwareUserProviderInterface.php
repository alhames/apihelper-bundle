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

use ApiHelperBundle\Account\AbstractAccount;
use ApiHelperBundle\Security\Core\Exception\ServiceAccountNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ServiceAccountAwareUserProviderInterface.
 */
interface ServiceAccountAwareUserProviderInterface extends UserProviderInterface
{
    /**
     * @param AbstractAccount $account
     *
     * @return UserInterface
     *
     * @throws ServiceAccountNotFoundException if the user is not found
     */
    public function loadUserByServiceAccount(AbstractAccount $account);
}
