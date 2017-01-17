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

    /** @var string[] */
    protected $loaded = [];

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

    /** @var array */
    protected $friends;

    /** @var string */
    protected $picture;

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
            $this->loaded[] = 'access_token';
            $this->client->setAccessToken($data['access_token']);
        }

        if (isset($data['refresh_token'])) {
            $this->refreshToken = $data['refresh_token'];
            $this->loaded[] = 'refresh_token';
        }

        if (!empty($data['expires_in'])) {
            $this->expiresAt = new \DateTime();
            $this->expiresAt->add(new \DateInterval('PT'.$data['expires_in'].'S'));
            $this->loaded[] = 'expires_at';
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
        if (!in_array('id', $this->loaded, true)) {
            $this->load('id');
        }

        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getFirstName()
    {
        if (!in_array('first_name', $this->loaded, true)) {
            $this->load('first_name');
        }

        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName()
    {
        if (!in_array('last_name', $this->loaded, true)) {
            $this->load('last_name');
        }

        return $this->lastName;
    }

    /**
     * @return string|null
     */
    public function getLink()
    {
        if (!in_array('link', $this->loaded, true)) {
            $this->load('link');
        }

        return $this->link;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        if (!in_array('email', $this->loaded, true)) {
            $this->load('email');
        }

        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getNickname()
    {
        if (!in_array('nickname', $this->loaded, true)) {
            $this->load('nickname');
        }

        return $this->nickname;
    }

    /**
     * @return string|null
     */
    public function getGender()
    {
        if (!in_array('gender', $this->loaded, true)) {
            $this->load('gender');
        }

        return $this->gender;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday()
    {
        if (!in_array('birthday', $this->loaded, true)) {
            $this->load('birthday');
        }

        return $this->birthday;
    }

    /**
     * @return string|null
     */
    public function getLocation()
    {
        if (!in_array('location', $this->loaded, true)) {
            $this->load('location');
        }

        return $this->location;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        if (!in_array('access_token', $this->loaded, true)) {
            $this->load('access_token');
        }

        return $this->accessToken;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        if (!in_array('refresh_token', $this->loaded, true)) {
            $this->load('refresh_token');
        }

        return $this->refreshToken;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpiresAt()
    {
        if (!in_array('expires_at', $this->loaded, true)) {
            $this->load('expires_at');
        }

        return $this->expiresAt;
    }

    /**
     * @return array List of friend ids
     */
    public function getFriends()
    {
        if (!in_array('friends', $this->loaded, true)) {
            $this->load('friends');
        }

        return $this->friends ?: [];
    }

    /**
     * @return string
     */
    public function getPicture()
    {
        if (!in_array('picture', $this->loaded, true)) {
            $this->load('picture');
        }

        return $this->picture;
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
