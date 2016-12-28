<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Account;

use ApiHelper\Core\OAuth2ClientInterface;

/**
 * Class AbstractAccount.
 */
abstract class AbstractAccount
{
    /** @var OAuth2ClientInterface */
    protected $client;

    /** @var string */
    protected $service;

    /** @var int|string */
    protected $id;

    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var string */
    protected $link;

    /** @var string */
    protected $email;

    /** @var string */
    protected $nickname;

    /** @var string */
    protected $gender;

    /** @var \DateTime */
    protected $birthday;

    /** @var string */
    protected $location;

    /** @var string */
    protected $accessToken;

    /** @var string */
    protected $refreshToken;

    /** @var \DateTime */
    protected $expiresAt;

    /**
     * AbstractAccount constructor.
     *
     * @param string                $service
     * @param OAuth2ClientInterface $client
     */
    public function __construct($service, OAuth2ClientInterface $client)
    {
        $this->service = $service;
        $this->client = $client;
    }

    /**
     * Account initialization from the token response data.
     *
     * @param array $data
     *
     * @return static
     */
    public function init(array $data)
    {
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
        }

        if (isset($data['refresh_token'])) {
            $this->refreshToken = $data['refresh_token'];
        }

        if (!empty($data['expires_in'])) {
            $this->expiresAt = new \DateTime('@'.(time() + $data['expires_in']));
        }

        return $this;
    }

    /**
     * @param string $code
     *
     * @return static
     */
    public function authorize($code)
    {
        return $this->init($this->client->authorize($code));
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->load('id');
        }

        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        if (null === $this->firstName) {
            $this->load('first_name');
        }

        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        if (null === $this->lastName) {
            $this->load('last_name');
        }

        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        if (null === $this->link) {
            $this->load('link');
        }

        return $this->link;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        if (null === $this->email) {
            $this->load('email');
        }

        return $this->email;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        if (null === $this->nickname) {
            $this->load('nickname');
        }

        return $this->nickname;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        if (null === $this->gender) {
            $this->load('gender');
        }

        return $this->gender;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        if (null === $this->birthday) {
            $this->load('birthday');
        }

        return $this->birthday;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        if (null === $this->location) {
            $this->load('location');
        }

        return $this->location;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        if (null === $this->accessToken) {
            $this->load('access_token');
        }

        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        if (null === $this->refreshToken) {
            $this->load('refresh_token');
        }

        return $this->refreshToken;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        if (null === $this->expiresAt) {
            $this->load('expires_at');
        }

        return $this->expiresAt;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * @param string $option
     */
    abstract protected function load($option);
}
