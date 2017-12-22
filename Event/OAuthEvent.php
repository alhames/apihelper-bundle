<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
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

    /** @var Request */
    protected $request;

    /**
     * LoginEvent constructor.
     *
     * @param Request             $request
     * @param string              $service
     * @param string              $state
     * @param TokenInterface|null $token
     */
    public function __construct(Request $request, $service, $state, TokenInterface $token = null)
    {
        $this->request = $request;
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
     * @return TokenInterface|null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
