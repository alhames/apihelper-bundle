<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\Core;

use ApiHelper\Core\ClientInterface;
use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelper\Core\ParserInterface;
use Alhames\ApiHelperBundle\Account\AbstractAccount;

/**
 * Class ServiceManager.
 */
class ServiceManager
{
    /** @var array  */
    protected $config;

    /** @var ClientInterface[] */
    protected $clients = [];

    /**
     * ServiceManager constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return array_key_exists($alias, $this->config);
    }

    /**
     * @param string $alias
     *
     * @return ClientInterface|OAuth2ClientInterface|ParserInterface
     */
    public function get($alias)
    {
        if (!array_key_exists($alias, $this->clients)) {
            $this->clients[$alias] = $this->create($alias);
        }

        return $this->clients[$alias];
    }

    /**
     * @param string $alias
     * @param array  $options
     *
     * @return ClientInterface|OAuth2ClientInterface|ParserInterface
     */
    public function create($alias, array $options = [])
    {
        if (!$this->has($alias)) {
            throw new \LogicException(sprintf('Invalid service "%s".', $alias));
        }

        $class = $this->config[$alias]['client_class'];

        return new $class(array_merge($this->config[$alias], $options));
    }

    /**
     * @param string                $alias
     * @param OAuth2ClientInterface $client
     *
     * @return AbstractAccount
     */
    public function createAccount($alias, OAuth2ClientInterface $client = null)
    {
        if (!isset($this->config[$alias]['account_class'])) {
            throw new \LogicException(sprintf('Service %s not found or does not have an account.', $alias));
        }

        $client = $client ?: $this->get($alias);
        $class = $this->config[$alias]['account_class'];

        return new $class($alias, $client);
    }

    /**
     * @param string $alias
     * @param string $providerKey
     *
     * @return bool
     */
    public function isLoginSupported($alias, $providerKey)
    {
        if (!$this->has($alias)) {
            return false;
        }

        if (is_bool($this->config[$alias]['security_login'])) {
            return $this->config[$alias]['security_login'];
        }

        return in_array($providerKey, $this->config[$alias]['security_login'], true);
    }

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function isConnectSupported($alias)
    {
        return $this->has($alias) && $this->config[$alias]['security_connect'];
    }

    /**
     * @return string[]
     */
    public function getConnectSupportedServices()
    {
        $services = [];

        foreach ($this->config as $alias => $config) {
            if ($config['security_connect']) {
                $services[] = $alias;
            }
        }

        return $services;
    }

    /**
     * @return string[]
     */
    public function getLoginSupportedServices($providerKey)
    {
        $services = [];

        foreach ($this->config as $alias => $config) {
            if (true === $config['security_login']) {
                $services[] = $alias;
            } elseif (is_array($config['security_login']) && in_array($providerKey, $config['security_login'], true)) {
                $services[] = $alias;
            }
        }

        return $services;
    }
}
