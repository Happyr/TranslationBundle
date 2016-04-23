<?php

namespace Happyr\TranslationBundle\tests\Functional;

class BundleInitializationTest extends BaseTestCase
{

    /**
     * @test
     */
    public function bundle_will_install_with_no_errors()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $container->get('happyr.translation.service.loco');
        $container->get('happyr.translation.service.blackhole');
        $container->get('happyr.translation.service.filesystem');
    }


}
