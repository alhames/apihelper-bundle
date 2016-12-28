<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Security\Http\Firewall;

use ApiHelperBundle\Security\Core\Authentication\Provider\OAuthProvider;
use ApiHelperBundle\Security\Core\Authentication\Token\OAuthToken;
use ApiHelperBundle\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

/**
 * Class OAuthListener.
 */
class OAuthListener extends AbstractAuthenticationListener
{
    /**
     * {@inheritdoc}
     */
    public function requiresAuthentication(Request $request)
    {
        if (!$request->isMethod('GET') || !parent::requiresAuthentication($request)) {
            return false;
        }

        if (null === $request->attributes->get('service')) {
            return false;
        }

        if (null === ($state = $request->query->get('state'))) {
            return false;
        }

        $options = $request->getSession()->get(Security::STATE_ID.$state);
        if (!isset($options['action']) || Security::ACTION_LOGIN !== $options['action'] || $this->providerKey !== $options['provider']) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        $state = $request->query->get('state');
        $request->getSession()->remove(Security::STATE_ID.$state);

        if (null === ($code = $request->query->get('code'))) {
            throw new BadCredentialsException('OAuth code is not found.');
        }

        $token = new OAuthToken(
            OAuthProvider::USERNAME_NONE_PROVIDED,
            ['service' => $request->attributes->get('service'), 'code' => $code, 'state' => $state],
            $this->providerKey
        );

        return $this->authenticationManager->authenticate($token);
    }
}
