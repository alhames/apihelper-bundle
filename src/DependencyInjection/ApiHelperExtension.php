<?php

namespace ApiHelperBundle\DependencyInjection;

use ApiHelper\Core\OAuth2ClientInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');

        $oauth2Services = [];

        if (!empty($config['services'])) {
            foreach ($config['services'] as $service => &$client) {
                if (null === $client) {
                    $client = [];
                }

                if (!isset($client['class'])) {
                    $client['class'] = 'ApiHelper\\'.ucfirst($service).'\\'.ucfirst($service).'Client';
                }

                if (!class_exists($client['class'])) {
                    throw new \LogicException(sprintf('Invalid client class for service "%s": %s.', $service, $client['class']));
                }

                if (is_subclass_of($client['class'], OAuth2ClientInterface::class)) {
                    $oauth2Services[] = $service;
                }
            }
            unset($client);

            $container->setParameter('apihelper.services', $config['services']);
        }

        if ($config['authentication']['enabled']) {

            if (preg_match('#^([\\\\a-z0-9]+)\\\\Controller\\\\([a-z0-9]+)Controller$#i', $config['authentication']['controller'], $matches)) {
                $container->setParameter('apihelper.auth.controller', str_replace('\\', '', $matches[1]).':'.$matches[2]);
            } else {
                $container->setParameter('apihelper.auth.controller', $config['authentication']['controller']);
            }

            if (!isset($config['authentication']['routes'])) {
                throw new \LogicException('apihelper.authentication.routes must be defined.');
            }

            $container->setParameter('apihelper.route.default', $config['authentication']['routes']['default']);
            $container->setParameter('apihelper.route.login', $config['authentication']['routes']['login']);

            $oauth2Services = array_intersect($oauth2Services, $config['authentication']['services']);
        } else {
            $oauth2Services = [];
        }

        $container->setParameter('apihelper.oauth2.filter', implode('|', array_unique($oauth2Services)));
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'apihelper';
    }
}
