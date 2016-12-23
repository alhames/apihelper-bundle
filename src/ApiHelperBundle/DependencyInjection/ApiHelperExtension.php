<?php

namespace ApiHelperBundle\DependencyInjection;

use ApiHelper\Core\OAuth2ClientInterface;
use ApiHelperBundle\Controller\AbstractServiceController;
use PhpHelper\Str;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class ApiHelperExtension
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

            if (!empty($config['controller'])) {
                if (!class_exists($config['controller']) || !is_subclass_of($config['controller'], AbstractServiceController::class)) {
                    throw new \LogicException(sprintf('Invalid controller class: %s.', $config['controller']));
                }

                $container->setParameter('apihelper.security.controller', preg_replace('#^\\\\?([a-z0-9_\\\\]+)\\\\Controller\\\\([a-z0-9_]+)Controller$#i', '$1:$2', $config['controller']));
            }

            $container->setParameter('apihelper.security.requirements.login', implode('|', array_unique($loginServices)));
            $container->setParameter('apihelper.security.requirements.connect', implode('|', array_unique($connectServices)));
            $container->setParameter('apihelper.security.requirements.callback', implode('|', array_unique(array_merge($loginServices, $connectServices))));

            $container->getDefinition('apihelper.manager')->replaceArgument(0, $config['services']);
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
