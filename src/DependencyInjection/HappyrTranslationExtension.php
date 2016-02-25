<?php

namespace Happyr\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class HappyrTranslationExtension extends Extension
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

        if ($config['auto_add_assets']) {
            $loader->load('autoAdd.yml');
        }

        $container->setParameter('translation.toolbar.allow_edit', $config['allow_edit']);

        $targetDir = rtrim($config['target_dir'], '/');
        $container->findDefinition('happyr.translation.filesystem')
            ->replaceArgument(2, $targetDir)
            ->replaceArgument(3, $config['file_extension']);

        $this->configureLoaderAndDumper($container, $config['file_extension']);

        $container->getDefinition('happyr.translation.request_manager')
            ->replaceArgument(0, new Reference($config['httplug_client']))
            ->replaceArgument(1, new Reference($config['httplug_message_factory']));

        /*
         * Set alias for the translation service
         */
        $container->setAlias('happyr.translation', 'happyr.translation.service.'.$config['translation_service']);

        $container->findDefinition('happyr.translation.service.loco')
            ->replaceArgument(2, $config['projects']);
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

    /**
     * @param ContainerBuilder $container
     * @param string           $fileExtension
     */
    protected function configureLoaderAndDumper(ContainerBuilder $container, $fileExtension)
    {
        if ($fileExtension === 'xlf') {
            $fileExtension = 'xliff';
        }

        $loader = $container->register('happyr.translation.loader', sprintf('Symfony\Component\Translation\Loader\%sFileLoader', ucfirst($fileExtension)));
        $loader->addTag('translation.loader', ['alias' => $fileExtension]);

        $dumper = $container->register('happyr.translation.dumper', sprintf('Symfony\Component\Translation\Dumper\%sFileDumper', ucfirst($fileExtension)));
        $dumper->addTag('translation.dumper', ['alias' => $fileExtension]);
    }
}
