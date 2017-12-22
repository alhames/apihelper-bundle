<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\DependencyInjection;

use Alhames\ApiHelperBundle\Core\CaptchaManager;
use Alhames\ApiHelperBundle\Core\ServiceManager;
use ApiHelper\Client\ReCaptchaClient;
use ApiHelper\Core\OAuth2ClientInterface;
use PhpHelper\Str;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class AlhamesApiHelperExtension.
 */
class AlhamesApiHelperExtension extends Extension
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
        $ymlLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $ymlLoader->load('services.yml');

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
                    $client['account_class'] = 'Alhames\ApiHelperBundle\Account\\'.$service.'Account';
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

            $container->getDefinition(ServiceManager::class)->setArgument('$config', $config['services']);
        }

        $this->initCaptcha($config['captcha'], $container);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    protected function initCaptcha(array $config, ContainerBuilder $container)
    {
        if (!$config['enabled']) {
            $container->removeDefinition(CaptchaManager::class);

            return;
        }

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new \LogicException('client_id and client_secret must be defined.');
        }

        $captchaManager = $container->getDefinition(CaptchaManager::class);
        $clientOptions = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ];
        $client = new Definition(ReCaptchaClient::class, [$clientOptions]);
        $captchaManager->setArgument('$client', $client);
        if (isset($config['storage'])) {
            $captchaManager->setArgument('$storage', new Reference($config['storage']));
        }
        $captchaManager->setArgument('$routes', $config['routes']);
        $captchaManager->setArgument('$ttl', $config['ttl']);
        $captchaManager->setArgument('$storageKey', $config['storage_key']);
    }
}
