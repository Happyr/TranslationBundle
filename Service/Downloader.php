<?php

namespace Happyr\LocoBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Happyr\LocoBundle\Entity\EventUserInterface;
use Happyr\LocoBundle\Entity\Log;
use Happyr\LocoBundle\Event\TrackableEventInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * @author Cliff Odijk (cmodijk)
 */
class Downloader
{


    public function download()
    {
        // Batch downloader
        $batch = BatchBuilder::factory()
            ->transferRequests(10)
            ->autoFlushAt(10)
            ->build();
        // Doorlopen bestanden
        foreach ($this->config as $config) {
            foreach ($config["locales"] as $localeKey => $localeValue) {
                foreach ($config["domains"] as $domain) {
                    // Dir maken als deze niet bestaat
                    if (!is_dir($config["target"])) {
                        mkdir($config["target"], 0777, true);
                    }
                    // Basis query
                    $query = array(
                        "key"		=> $config["key"],
                        "format"	=> $config["format"],
                        "index"		=> $config["index"],
                        "filter"	=> $domain,
                    );
                    // Build url
                    $url		= sprintf("export/locale/%s.%s?%s", $localeValue, $config["extension"], http_build_query($query));
                    $savePath	= sprintf("%s/%s.%s.%s", $config["target"], $domain, $localeKey, $config["extension"]);
                    // Downloaden
                    $batch->add($this->client->get($url, array(), array( "save_to" => $savePath)));
                }
            }
        }
        // Alles binnen halen
        $batch->flush();
    }
}
