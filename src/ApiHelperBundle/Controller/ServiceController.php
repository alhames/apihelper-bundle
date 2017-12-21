<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Controller;

use ApiHelper\Exception\RequestTokenException;
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelperBundle\Core\ServiceAccountConnectorInterface;
use ApiHelperBundle\Core\ServiceManager;
use ApiHelperBundle\Event\OAuthEvent;
use ApiHelperBundle\Exception\ConnectAccountException;
use ApiHelperBundle\Security\Core\Security;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class ServiceController.
 */
class ServiceController
{
    use TargetPathTrait;

    /** @var array */
    protected $options;

    /** @var ServiceManager */
    protected $serviceManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var HttpUtils */
    protected $utils;

    /** @var TokenGeneratorInterface */
    protected $tokenGenerator;

    /** @var ServiceAccountConnectorInterface */
    protected $serviceAccountConnector;

    /**
     * AbstractServiceController constructor.
     *
     * @param array                            $options
     * @param ServiceManager                   $serviceManager
     * @param TokenStorageInterface            $tokenStorage
     * @param EventDispatcherInterface         $dispatcher
     * @param HttpUtils                        $utils
     * @param TokenGeneratorInterface          $tokenGenerator
     * @param ServiceAccountConnectorInterface $serviceAccountConnector
     */
    public function __construct(
        array $options,
        ServiceManager $serviceManager,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
        HttpUtils $utils,
        TokenGeneratorInterface $tokenGenerator,
        ServiceAccountConnectorInterface $serviceAccountConnector
    ) {
        $this->options = $options;
        $this->serviceManager = $serviceManager;
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $dispatcher;
        $this->utils = $utils;
        $this->tokenGenerator = $tokenGenerator;
        $this->serviceAccountConnector = $serviceAccountConnector;
    }

    /**
     * @param Request $request
     * @param string  $service
     * @param string  $providerKey
     *
     * @return RedirectResponse
     */
    public function loginAction(Request $request, $service, $providerKey = 'main')
    {
        if (!isset($this->options[$providerKey])) {
            throw new \LogicException(sprintf('Options for provider "%s" is not defined.', $providerKey));
        }

        if (!$this->serviceManager->isLoginSupported($service, $providerKey)) {
            throw new NotFoundHttpException();
        }

        $targetUrl = $this->determineTargetUrl($request, $this->options, $providerKey);
        if ($this->serviceAccountConnector->isAuthorized()) {
            return new RedirectResponse($this->utils->generateUri($request, $targetUrl));
        }

        $state = $this->tokenGenerator->generateToken();
        $this->saveTargetPath($request->getSession(), $providerKey, $this->utils->generateUri($request, $targetUrl));
        $request->getSession()->set(Security::STATE_ID.$state, ['action' => Security::ACTION_LOGIN, 'provider' => $providerKey]);

        $client = $this->serviceManager->get($service);
        $client->setRedirectUri($this->utils->generateUri($request, $this->options[$providerKey]['check_path']));

        $token = $this->tokenStorage->getToken();
        $this->dispatcher->dispatch(OAuthEvent::LOGIN_START, new OAuthEvent($request, $service, $state, $token));

        return new RedirectResponse($client->getAuthorizationUrl($state));
    }

    /**
     * @param Request $request
     * @param string  $service
     *
     * @return RedirectResponse
     */
    public function connectAction(Request $request, $service)
    {
        if (!$this->serviceManager->isConnectSupported($service)) {
            throw new NotFoundHttpException();
        }

        if (!$this->serviceAccountConnector->isAuthorized()) {
            throw new AccessDeniedException();
        }

        $client = $this->serviceManager->get($service);
        $state = $this->tokenGenerator->generateToken();

        $request->getSession()->set(Security::STATE_ID.$state, ['action' => Security::ACTION_CONNECT]);

        $token = $this->tokenStorage->getToken();
        $this->dispatcher->dispatch(OAuthEvent::CONNECT_START, new OAuthEvent($request, $service, $state, $token));

        return new RedirectResponse($client->getAuthorizationUrl($state));
    }

    /**
     * @param Request $request
     * @param string  $service
     *
     * @return RedirectResponse
     */
    public function callbackAction(Request $request, $service)
    {
        $state = $request->query->get('state');
        if (!$state) {
            return $this->serviceAccountConnector->createErrorRedirect('invalid_state');
        }

        $options = $request->getSession()->get(Security::STATE_ID.$state);
        if (!isset($options['action']) || Security::ACTION_CONNECT !== $options['action']) {
            return $this->serviceAccountConnector->createErrorRedirect('invalid_action');
        }

        if (!$this->serviceManager->isConnectSupported($service)) {
            throw new NotFoundHttpException();
        }

        if (!$this->serviceAccountConnector->isAuthorized()) {
            throw new AccessDeniedException();
        }

        $code = $request->query->get('code');
        if (!$code) {
            return $this->serviceAccountConnector->createErrorRedirect('invalid_code');
        }

        $account = $this->serviceManager->createAccount($service);

        try {
            $account->authorize($code);
        } catch (RequestTokenException $e) {
            return $this->serviceAccountConnector->createErrorRedirect('account_request', $e);
        } catch (ServiceUnavailableException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        } catch (ConnectException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        }

        try {
            $response = $this->serviceAccountConnector->connectAccount($request, $account);
        } catch (ConnectAccountException $e) {
            return $this->serviceAccountConnector->createErrorRedirect($e->getMessage(), $e);
        }

        $token = $this->tokenStorage->getToken();
        $this->dispatcher->dispatch(OAuthEvent::CONNECT_FINISH, new OAuthEvent($request, $service, $state, $token));

        return $response;
    }

    /**
     * @see \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler::determineTargetUrl()
     *
     * @param Request $request
     * @param array   $options
     * @param string  $providerKey
     *
     * @return string
     */
    protected function determineTargetUrl(Request $request, array $options, $providerKey)
    {
        $opt = $options[$providerKey];

        if ($opt['always_use_default_target_path']) {
            return $opt['default_target_path'];
        }

        if ($targetUrl = $request->query->get($opt['target_path_parameter'])) {
            return $targetUrl;
        }

        if ($targetUrl = $this->getTargetPath($request->getSession(), $providerKey)) {
            $this->removeTargetPath($request->getSession(), $providerKey);

            return $targetUrl;
        }

        if ($opt['use_referer']
            && ($targetUrl = $request->headers->get('Referer'))
            && $targetUrl !== $this->utils->generateUri($request, $opt['login_path'])
        ) {
            return $targetUrl;
        }

        return $opt['default_target_path'];
    }
}
