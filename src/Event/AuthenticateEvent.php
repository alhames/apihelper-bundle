<?php

namespace ApiHelperBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AuthEvent.
 */
class AuthenticateEvent extends Event
{
    const LOGIN_START = 'apihelper.login.start';
    const LOGIN_FINISH = 'apihelper.login.finish';
    const CONNECT_START = 'apihelper.connect.start';
    const CONNECT_FINISH = 'apihelper.connect.finish';

    /** @var string */
    protected $state;

    /**
     * LoginEvent constructor.
     *
     * @param string $state
     */
    public function __construct($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }
}
