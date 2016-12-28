<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Security\Core\Authentication\Provider;

use ApiHelper\Exception\RequestTokenException;
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelperBundle\Core\ServiceManager;
use ApiHelperBundle\Security\Core\Authentication\Token\OAuthToken;
use ApiHelperBundle\Security\Core\Exception\ServiceAccountNotFoundException;
use ApiHelperBundle\Security\Core\User\ServiceAccountAwareUserProviderInterface;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

/**
 * Class OAuthProvider.
 */
class OAuthProvider implements AuthenticationProviderInterface
{
    /** @var ServiceManager */
    protected $serviceManager;

    /** @var ServiceAccountAwareUserProviderInterface */
    protected $userProvider;

    /** @var UserCheckerInterface */
    protected $userChecker;

    /** @var string */
    protected $providerKey;

    /**
     * OAuthProvider constructor.
     *
     * @param ServiceAccountAwareUserProviderInterface $userProvider
     * @param UserCheckerInterface                     $userChecker
     * @param string                                   $providerKey
     * @param ServiceManager                           $serviceManager
     */
    public function __construct(ServiceAccountAwareUserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, ServiceManager $serviceManager)
    {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->serviceManager = $serviceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuthToken && $this->providerKey === $token->getProviderKey();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        $credentials = $token->getCredentials();
        if (!isset($credentials['service']) || !isset($credentials['code'])) {
            $exception = new BadCredentialsException('Service or code is undefined.');
            $exception->setToken($token);
            throw $exception;
        }

        $account = $this->serviceManager->createAccount($credentials['service']);

        try {
            $account->authorize($credentials['code']);
            $accountId = $account->getId();
        } catch (RequestTokenException $e) {
            $exception = new BadCredentialsException($e->getMessage(), 0, $e);
            $exception->setToken($token);
            throw $exception;
        } catch (ServiceUnavailableException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        } catch (ConnectException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        } catch (\Exception $e) {
            $exception = new AuthenticationServiceException($e->getMessage(), 0, $e);
            $exception->setToken($token);
            throw $exception;
        }

        try {
            $user = $this->userProvider->loadUserByServiceAccount($credentials['service'], $accountId);
        } catch (ServiceAccountNotFoundException $e) {
            $e->setAccountId($accountId);
            $e->setService($credentials['service']);
            $e->setToken($token);
            throw $e;
        } catch (\Exception $e) {
            $e = new AuthenticationServiceException($e->getMessage(), 0, $e);
            $e->setToken($token);
            throw $e;
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new OAuthToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }
}
