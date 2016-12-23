<?php

namespace ApiHelperBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class OAuthEvent.
 */
class OAuthEvent extends Event
{
    const LOGIN_START = 'apihelper.login.start';
    const CONNECT_START = 'apihelper.connect.start';
    const CONNECT_FINISH = 'apihelper.connect.finish';

    /** @var string */
    protected $service;

    /** @var string */
    protected $state;

    /** @var TokenInterface */
    protected $token;

    /**
     * LoginEvent constructor.
     *
     * @param string         $service
     * @param string         $state
     * @param TokenInterface $token
     */
    public function __construct($service, $state, TokenInterface $token = null)
    {
        $this->service = $service;
        $this->state = $state;
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return TokenInterface
     */
    public function getToken()
    {
        return $this->token;
    }
}
