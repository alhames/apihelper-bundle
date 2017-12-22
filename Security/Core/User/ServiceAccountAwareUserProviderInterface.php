<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\Security\Core\User;

use Alhames\ApiHelperBundle\Account\AbstractAccount;
use Alhames\ApiHelperBundle\Security\Core\Exception\ServiceAccountNotFoundException;
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
     * @throws ServiceAccountNotFoundException if the user is not found
     *
     * @return UserInterface
     */
    public function loadUserByServiceAccount(AbstractAccount $account);
}
