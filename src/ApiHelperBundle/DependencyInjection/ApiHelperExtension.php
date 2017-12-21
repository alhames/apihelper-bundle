<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\DependencyInjection;

use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelperBundle\Controller\ServiceController;
use PhpHelper\Str;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class ApiHelperExtension.
 */
class ApiHelperExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.xml');
        $loader->load('services.xml');

        if (class_exists('Twig_Extension') && $config['captcha']['enabled']) {
            $loader->load('twig.xml');
        }

        $connectServices = [];
        $loginServices = [];

        if (!empty($config['services'])) {
            foreach ($config['services'] as $alias => &$client) {
                if (null === $client) {
                    $client = [];
                }

                if (!isset($client['service'])) {
                    $client['service'] = $alias;
                }

                $service = Str::convertCase($client['service'], Str::CASE_CAMEL_UPPER);

                if (!isset($client['client_class'])) {
                    $client['client_class'] = 'ApiHelper\Client\\'.$service.'Client';
                }

                if (!isset($client['account_class'])) {
                    $client['account_class'] = 'ApiHelperBundle\Account\\'.$service.'Account';
                }

                if (!class_exists($client['client_class'])) {
                    throw new \LogicException(sprintf('Invalid client class for service "%s": %s.', $client['service'], $client['client_class']));
                }

                if (!class_exists($client['account_class'])) {
                    $client['account_class'] = null;
                }

                if (is_subclass_of($client['client_class'], OAuth2ClientInterface::class)) {
                    if ($client['security_connect']) {
                        $connectServices[] = $alias;
                    }
                    if (empty($client['security_login'])) {
                        $client['security_login'] = false;
                    } else {
                        if ([null] === $client['security_login']) {
                            $client['security_login'] = true;
                        }
                        $loginServices[] = $alias;
                    }
                } else {
                    $client['security_connect'] = false;
                    $client['security_login'] = false;
                }
            }
            unset($client);

            $container->setParameter('apihelper.security.requirements.login', implode('|', array_unique($loginServices)));
            $container->setParameter('apihelper.security.requirements.connect', implode('|', array_unique($connectServices)));
            $container->setParameter('apihelper.security.requirements.callback', implode('|', array_unique(array_merge($loginServices, $connectServices))));

            $container->getDefinition('apihelper.manager')->replaceArgument(0, $config['services']);
        }

        if ($config['captcha']['enabled']) {
            if (empty($config['captcha']['client_id']) || empty($config['captcha']['client_secret'])) {
                throw new \LogicException('client_id and client_secret must be defined.');
            }

            $clientOptions = [
                'client_id' => $config['captcha']['client_id'],
                'client_secret' => $config['captcha']['client_secret'],
            ];

            $captchaManager = new DefinitionDecorator('apihelper.captcha.manager.abstract');
            $captchaManager->addArgument(new Definition('ApiHelper\Client\ReCaptchaClient', [$clientOptions]));
            $captchaManager->addArgument(isset($config['captcha']['storage']) ? new Reference($config['captcha']['storage']) : null);
            $captchaManager->addArgument($config['captcha']['routes']);
            $captchaManager->addArgument($config['captcha']['ttl']);
            $captchaManager->addArgument($config['captcha']['storage_key']);
            $container->setDefinition('apihelper.captcha.manager', $captchaManager);
        }
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'apihelper';
    }
}
