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

use ApiHelper\Client\ReCaptchaClient;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CaptchaManager.
 */
class CaptchaManager
{
    const REQUEST_PARAMETER_ID = 'g-recaptcha-response';

    /** @var ReCaptchaClient  */
    protected $client;

    /** @var CacheItemPoolInterface  */
    protected $storage;

    /** @var int[] */
    protected $failureMaxCount;

    /** @var int  */
    protected $ttl;

    /** @var string  */
    protected $storageKey;

    /**
     * CaptchaManager constructor.
     *
     * @param ReCaptchaClient             $client
     * @param CacheItemPoolInterface|null $storage
     * @param array                       $routes
     * @param int                         $ttl
     * @param string                      $storageKey
     */
    public function __construct(ReCaptchaClient $client, CacheItemPoolInterface $storage = null, array $routes = [], $ttl = 86400, $storageKey = 'apihelper.captcha.route_%s.ip_%s')
    {
        $this->client = $client;
        $this->storage = $storage;
        $this->failureMaxCount = $routes;
        $this->ttl = $ttl;
        $this->storageKey = $storageKey;
    }

    /**
     * @return string
     */
    public function getSiteKey()
    {
        return $this->client->getClientId();
    }

    /**
     * @param Request $request
     * @param string  $route
     *
     * @return bool
     */
    public function isRequired(Request $request, $route = null)
    {
        $route = $this->getRoute($request, $route);
        if (empty($this->failureMaxCount[$route])) {
            return true;
        }

        if (null === $this->storage) {
            throw new \LogicException('Storage is not defined.');
        }

        $cacheItem = $this->storage->getItem(sprintf($this->storageKey, $route, $request->getClientIp()));

        return $cacheItem->isHit() && $cacheItem->get() >= $this->failureMaxCount[$route];
    }

    /**
     * @param Request $request
     * @param string  $route
     */
    public function incrementFailureCount(Request $request, $route = null)
    {
        $route = $this->getRoute($request, $route);
        if (empty($this->failureMaxCount[$route])) {
            return;
        }

        if (null === $this->storage) {
            throw new \LogicException('Storage is not defined.');
        }

        $cacheItem = $this->storage->getItem(sprintf($this->storageKey, $route, $request->getClientIp()));
        $cacheItem->set($cacheItem->isHit() ? $cacheItem->get() + 1 : 1);
        $cacheItem->expiresAfter($this->ttl);
        $this->storage->save($cacheItem);
    }

    /**
     * @param Request $request
     * @param string  $route
     */
    public function resetFailureCount(Request $request, $route = null)
    {
        $route = $this->getRoute($request, $route);
        if (empty($this->failureMaxCount[$route])) {
            return;
        }

        if (null === $this->storage) {
            throw new \LogicException('Storage is not defined.');
        }

        $this->storage->deleteItem(sprintf($this->storageKey, $route, $request->getClientIp()));
    }

    /**
     * @param Request $request
     * @param string  $route
     *
     * @return bool
     */
    public function check(Request $request, $route = null)
    {
        if (!$this->isRequired($request, $route)) {
            return true;
        }

        $captchaResponse = $request->request->get(self::REQUEST_PARAMETER_ID);
        if (empty($captchaResponse)) {
            return false;
        }

        $success = $this->client->verify($captchaResponse, $request->getClientIp());
        if ($success) {
            $this->resetFailureCount($request, $route);
        }

        return $success;
    }

    /**
     * @param Request $request
     * @param string  $route
     *
     * @return string
     */
    protected function getRoute(Request $request, $route)
    {
        if (null !== $route) {
            return $route;
        }

        $route = $request->attributes->get('_route');
        if (null === $route) {
            throw new \InvalidArgumentException('Undefined route for captcha.');
        }

        return $route;
    }
}