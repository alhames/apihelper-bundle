<?php

namespace ApiHelperBundle\Controller;

use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelper\Exception\RequestTokenException;
use ApiHelper\Exception\ServiceUnavailableException;
use ApiHelperBundle\Event\AuthenticateEvent;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthController.
 */
abstract class AbstractAuthenticationController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    const CSRF_TOKEN_ID = 'apihelper_auth_%s';
    const ACTION_KEY = 'apihelper/auth/action:%s';
    const TARGET_KEY = 'apihelper/auth/target:%s';

    /**
     * Начинает процесс авторизации через Oauth 2
     *
     * @param Request $request
     * @param string  $service
     * @param string  $action
     *
     * @return Response
     */
    public function oauth2StartAction(Request $request, $service, $action)
    {
        $target = $request->query->get('target', $this->generateUrl($this->getParameter('apihelper.route.default')));

        if ('login' === $action && $this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($target);
        } elseif ('connect' === $action && !$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToError('not_authenticated');
        }

        $client = $this->get('apihelper.manager')->get($service);
        $state = $this->get('security.csrf.token_manager')->getToken(sprintf(self::CSRF_TOKEN_ID, $service))->getValue();

        $request->getSession()->set(sprintf(self::ACTION_KEY, $state), $action);
        $request->getSession()->set(sprintf(self::TARGET_KEY, $state), $target);


        $this->get('event_dispatcher')->dispatch(
            'connect' === $action ? AuthenticateEvent::CONNECT_START : AuthenticateEvent::LOGIN_START,
            new AuthenticateEvent($state)
        );

        return $this->redirect($client->getAuthUrl($state));
    }

    /**
     * @param Request $request
     * @param string  $service
     *
     * @return Response
     */
    public function oauth2FinishAction(Request $request, $service)
    {
        $state = $request->query->get('state');
        if (!$state || !$this->isCsrfTokenValid(sprintf(self::CSRF_TOKEN_ID, $service), $state)) {
            return $this->redirectToError('invalid_state');
        }

        $action = $request->getSession()->get(sprintf(self::ACTION_KEY, $state));
        if (!$action) {
            return $this->redirectToError('invalid_action');
        }

        $code = $request->query->get('code');
        if (!$code) {
            return $this->redirectToError('invalid_code');
        }

        if ('login' === $action && $this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $redirectUri = $this->generateUrl($this->getParameter('apihelper.route.default'));
            $redirectUri = $request->getSession()->get(sprintf(self::TARGET_KEY, $state), $redirectUri);

            return $this->redirect($redirectUri);
        } elseif ('connect' === $action && !$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToError('not_authenticated');
        }

        $client = $this->get('apihelper.manager')->get($service);

        try {
            $data = $client->requestAccessToken($code);

            return $this->{'do'.ucfirst($action)}($service, $client, $data);
        } catch (RequestTokenException $e) {
            return $this->redirectToError('account_request');
        } catch (ServiceUnavailableException $e) {
            return $this->catchUnavailableException($e);
        } catch (ConnectException $e) {
            return $this->catchUnavailableException($e);
        }
    }

    /**
     * @param string $message
     *
     * @return Response
     */
    protected function redirectToError($message)
    {
        $this->addFlash('error', $message);
        $route = $this->isGranted('IS_AUTHENTICATED_REMEMBERED') ? 'default' : 'login';

        return $this->redirectToRoute($this->getParameter('apihelper.route.'.$route));
    }

    /**
     * @param string                $service
     * @param OAuth2ClientInterface $client
     * @param array                 $data
     *
     * @return Response
     */
    abstract protected function doLogin($service, OAuth2ClientInterface $client, $data = null);

    /**
     * @param string                $service
     * @param OAuth2ClientInterface $client
     * @param array                 $data
     *
     * @return Response
     */
    abstract protected function doConnect($service, OAuth2ClientInterface $client, $data = null);

    /**
     * @param ServiceUnavailableException|ConnectException $exception
     *
     * @return Response
     */
    abstract protected function catchUnavailableException(\RuntimeException $exception);
}
