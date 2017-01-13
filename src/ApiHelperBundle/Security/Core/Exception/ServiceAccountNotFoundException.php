<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Security\Core\Exception;

use ApiHelperBundle\Account\AbstractAccount;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class ServiceAccountNotFoundException.
 */
class ServiceAccountNotFoundException extends AuthenticationException
{
    /** @var AbstractAccount */
    protected $account;

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Service account could not be found.';
    }

    /**
     * @return AbstractAccount
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param AbstractAccount $account
     *
     * @return static
     */
    public function setAccount(AbstractAccount $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->account, parent::serialize()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($this->account, $parentStr) = unserialize($str);
        parent::unserialize($parentStr);
    }
}
