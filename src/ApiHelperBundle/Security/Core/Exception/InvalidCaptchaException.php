<?php

namespace ApiHelperBundle\Security\Core\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class InvalidCaptchaException.
 */
class InvalidCaptchaException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'Invalid captcha.';
    }
}
