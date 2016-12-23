<?php

namespace ApiHelperBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class ServiceAccountNotFoundException.
 */
class ServiceAccountNotFoundException extends AuthenticationException
{
    /** @var string|int */
    protected $accountId;

    /** @var string */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Service account could not be found.';
    }

    /**
     * Get the account id.
     *
     * @return string|int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set the account id.
     *
     * @param string|int $accountId
     *
     * @return static
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get the service.
     *
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set the service.
     *
     * @param string $service
     *
     * @return static
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->accountId, $this->service, parent::serialize()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($this->accountId, $this->service, $parentData) = unserialize($str);

        parent::unserialize($parentData);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageData()
    {
        return ['{{ accountId }}' => $this->accountId, '{{ service }}' => $this->service];
    }
}
