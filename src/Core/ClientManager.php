<?php

namespace ApiHelperBundle\Core;

use ApiHelper\Core\ClientInterface;
use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelper\Core\ParserInterface;

/**
 * Class ClientManager.
 */
class ClientManager
{
    /** @var array  */
    protected $services;

    /**
     * ClientManager constructor.
     *
     * @param array $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * @param string $service
     * @param array  $options
     *
     * @return ClientInterface|OAuth2ClientInterface|ParserInterface
     */
    public function get($service, array $options = [])
    {
        if (!isset($this->services[$service])) {
            throw new \LogicException(sprintf('Invalid service "%s".', $service));
        }

        $class = $this->services[$service]['class'];

        return new $class(array_merge($this->services[$service], $options));
    }

    /**
     * @param string $service
     *
     * @return bool
     */
    public function has($service)
    {
        return isset($this->services[$service]);
    }
}
