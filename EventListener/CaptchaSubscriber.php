<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\EventListener;

use Alhames\ApiHelperBundle\Core\CaptchaManager;
use Alhames\ApiHelperBundle\Security\Core\Exception\InvalidCaptchaException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Class CaptchaSubscriber.
 */
class CaptchaSubscriber implements EventSubscriberInterface
{
    /** @var HttpUtils  */
    protected $httpUtils;

    /** @var CaptchaManager  */
    protected $captchaManager;

    /** @var RequestStack  */
    protected $requestStack;

    /** @var array  */
    protected $options;

    /** @var AuthenticationFailureHandlerInterface */
    protected $failureHandler;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onSecurityAuthenticationFailure',
        ];
    }

    /**
     * CaptchaListener constructor.
     *
     * @param HttpUtils                                  $httpUtils
     * @param CaptchaManager                             $captchaManager
     * @param RequestStack                               $requestStack
     * @param array|null                                 $options
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(HttpUtils $httpUtils, CaptchaManager $captchaManager, RequestStack $requestStack, array $options = null, AuthenticationFailureHandlerInterface $failureHandler = null)
    {
        $this->httpUtils = $httpUtils;
        $this->captchaManager = $captchaManager;
        $this->requestStack = $requestStack;
        $this->options = $options;
        $this->failureHandler = $failureHandler;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isMethod('POST')) { // todo: support only post
            return;
        }

        if (!$this->httpUtils->checkRequestPath($request, $this->options['check_path'])) {
            return;
        }

        if (!$this->captchaManager->check($request)) {
            $event->setResponse($this->failureHandler->onAuthenticationFailure($request, new InvalidCaptchaException('Invalid captcha.')));
        }
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onSecurityAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $this->captchaManager->incrementFailureCount($this->requestStack->getMasterRequest());
    }
}
