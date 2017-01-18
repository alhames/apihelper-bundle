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
use ApiHelperBundle\Account\AbstractAccount;
use ApiHelperBundle\Event\OAuthEvent;
use ApiHelperBundle\Exception\ConnectAccountException;
use ApiHelperBundle\Security\Core\Security;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class AbstractServiceController.
 */
abstract class AbstractServiceController extends Controller
{
    use TargetPathTrait;

    /**
     * @param Request $request
     * @param string  $service
     * @param string  $providerKey
     *
     * @return RedirectResponse
     */
    public function loginAction(Request $request, $service, $providerKey = 'main')
    {
        if (!$this->container->hasParameter('apihelper.security.options')) {
            throw new \LogicException('Firewall is not assigned.');
        }

        $options = $this->container->getParameter('apihelper.security.options');

        if (!isset($options[$providerKey])) {
            throw new \LogicException(sprintf('Options for provider "%s" is not defined.', $providerKey));
        }

        if (!$this->container->get('apihelper.manager')->isLoginSupported($service, $providerKey)) {
            throw new NotFoundHttpException();
        }

        $targetUrl = $this->determineTargetUrl($request, $options, $providerKey);
        if ($this->isAuthorized()) {
            return new RedirectResponse($this->generateUri($request, $targetUrl));
        }

        $state = $this->generateState();
        $this->saveTargetPath($request->getSession(), $providerKey, $this->generateUri($request, $targetUrl));
        $request->getSession()->set(Security::STATE_ID.$state, ['action' => Security::ACTION_LOGIN, 'provider' => $providerKey]);

        $client = $this->container->get('apihelper.manager')->get($service);
        $client->setRedirectUri($this->generateUri($request, $options[$providerKey]['check_path']));

        $token = $this->container->get('security.token_storage')->getToken();
        $this->container->get('event_dispatcher')->dispatch(OAuthEvent::LOGIN_START, new OAuthEvent($request, $service, $state, $token));

        return $this->redirect($client->getAuthorizationUrl($state));
    }

    /**
     * @param Request $request
     * @param string  $service
     *
     * @return RedirectResponse
     */
    public function connectAction(Request $request, $service)
    {
        if (!$this->container->get('apihelper.manager')->isConnectSupported($service)) {
            throw new NotFoundHttpException();
        }

        if (!$this->isAuthorized()) {
            throw $this->createAccessDeniedException();
        }

        $client = $this->container->get('apihelper.manager')->get($service);
        $state = $this->generateState();

        $request->getSession()->set(Security::STATE_ID.$state, ['action' => Security::ACTION_CONNECT]);

        $token = $this->container->get('security.token_storage')->getToken();
        $this->container->get('event_dispatcher')->dispatch(OAuthEvent::CONNECT_START, new OAuthEvent($request, $service, $state, $token));

        return $this->redirect($client->getAuthorizationUrl($state));
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
            return $this->createErrorRedirect('invalid_state');
        }

        $options = $request->getSession()->get(Security::STATE_ID.$state);
        if (!isset($options['action']) || Security::ACTION_CONNECT !== $options['action']) {
            return $this->createErrorRedirect('invalid_action');
        }

        $serviceManager = $this->container->get('apihelper.manager');
        if (!$serviceManager->isConnectSupported($service)) {
            throw new NotFoundHttpException();
        }

        if (!$this->isAuthorized()) {
            throw $this->createAccessDeniedException();
        }

        $code = $request->query->get('code');
        if (!$code) {
            return $this->createErrorRedirect('invalid_code');
        }

        $account = $serviceManager->createAccount($service);

        try {
            $account->authorize($code);
        } catch (RequestTokenException $e) {
            return $this->createErrorRedirect('account_request', $e);
        } catch (ServiceUnavailableException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        } catch (ConnectException $e) {
            throw new ServiceUnavailableHttpException(null, null, $e);
        }

        try {
            $response = $this->connectAccount($request, $account);
        } catch (ConnectAccountException $e) {
            return $this->createErrorRedirect($e->getMessage(), $e);
        }

        $token = $this->container->get('security.token_storage')->getToken();
        $this->container->get('event_dispatcher')->dispatch(OAuthEvent::CONNECT_FINISH, new OAuthEvent($request, $service, $state, $token));

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
        if ($options[$providerKey]['always_use_default_target_path']) {
            return $options[$providerKey]['default_target_path'];
        }

        if ($targetUrl = $request->query->get($options[$providerKey]['target_path_parameter'])) {
            return $targetUrl;
        }

        if ($targetUrl = $this->getTargetPath($request->getSession(), $providerKey)) {
            $this->removeTargetPath($request->getSession(), $providerKey);

            return $targetUrl;
        }

        if ($options[$providerKey]['use_referer']
            && ($targetUrl = $request->headers->get('Referer'))
            && $targetUrl !== $this->generateUri($request, $options[$providerKey]['login_path'])
        ) {
            return $targetUrl;
        }

        return $options[$providerKey]['default_target_path'];
    }

    /**
     * Generates a URI, based on the given path or absolute URL.
     *
     * @see \Symfony\Component\Security\Http\HttpUtils::generateUri()
     *
     * @param Request $request A Request instance
     * @param string  $path    A path (an absolute path (/foo), an absolute URL (http://...), or a route name (foo))
     *
     * @return string An absolute URL
     */
    protected function generateUri($request, $path)
    {
        if (0 === strpos($path, 'http') || !$path) {
            return $path;
        }

        if ('/' === $path[0]) {
            return $request->getUriForPath($path);
        }

        $url = $this->get('router')->generate($path, $request->attributes->all(), UrlGeneratorInterface::ABSOLUTE_URL);
        $position = strpos($url, '?');
        if (false !== $position) {
            $url = substr($url, 0, $position);
        }

        return $url;
    }

    /**
     * @see \Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator::generateToken()
     *
     * @param int $entropy
     *
     * @return string
     */
    protected function generateState($entropy = 256)
    {
        $bytes = random_bytes($entropy / 8);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * @return bool
     */
    protected function isAuthorized()
    {
        return $this->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    /**
     * @param string     $message
     * @param \Exception $exception
     *
     * @return RedirectResponse
     */
    abstract protected function createErrorRedirect($message, \Exception $exception = null);

    /**
     * @param Request         $request
     * @param AbstractAccount $account
     *
     * @throws ConnectAccountException
     *
     * @return RedirectResponse
     */
    abstract protected function connectAccount(Request $request, AbstractAccount $account);
}
