<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\Twig\Extension;

use Alhames\ApiHelperBundle\Core\CaptchaManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ApiHelperExtension.
 */
class ApiHelperExtension extends \Twig_Extension
{
    /** @var CaptchaManager  */
    protected $captchaManager;

    /** @var RequestStack  */
    protected $requestStack;

    /**
     * ApiHelperExtension constructor.
     *
     * @param CaptchaManager $captchaManager
     * @param RequestStack   $requestStack
     */
    public function __construct(CaptchaManager $captchaManager, RequestStack $requestStack)
    {
        $this->captchaManager = $captchaManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('apihelper_captcha', [$this, 'getCaptcha'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $route
     * @param array  $options
     *
     * @return string
     */
    public function getCaptcha($route = null, array $options = [])
    {
        if (!$this->captchaManager->isRequired($this->requestStack->getCurrentRequest(), $route)) {
            return '';
        }

        $attributes = [];
        $options['sitekey'] = $this->captchaManager->getSiteKey();
        foreach ($options as $key => $value) {
            $attributes[] = 'data-'.$key.'="'.htmlspecialchars($value).'"';
        }

        return '<div class="g-recaptcha" '.implode(' ', $attributes).'></div>';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'apihelper';
    }
}
