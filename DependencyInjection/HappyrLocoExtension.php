<?php

namespace Happyr\LocoBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class HappyrLocoExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $this->copyValuesFromParentToProject('locales', $config);
        $this->copyValuesFromParentToProject('domains', $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        /*
         * Set an alias for the adapter
         */
        $adapter = $config['http_adapter'];
        if ($adapter === 'guzzle5' || $adapter === 'guzzle6') {
            //Use one of our adapters
            $adapter = 'happyr.loco.http_adapter.'.$adapter;
        }
        $container->setAlias('happyr.loco.http_adapter', $adapter);

        $container->findDefinition('happyr.loco.downloader')
            ->replaceArgument(1, $config['projects'])
            ->replaceArgument(2, $config['target_dir']);
    }

    /**
     * Copy the parent configuration to the children.
     *
     * @param string $key
     * @param array  $config
     */
    private function copyValuesFromParentToProject($key, array &$config)
    {
        if (empty($config[$key])) {
            return;
        }

        foreach ($config['projects'] as &$project) {
            if (empty($project[$key])) {
                $project[$key] = $config[$key];
            }
        }
    }
}
