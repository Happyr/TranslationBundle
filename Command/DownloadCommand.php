<?php

namespace Happyr\LocoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Cliff Odijk (cmodijk)
 */
class DownloadCommand extends ContainerAwareCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('translation:loco:download')
            ->setDescription('Download latest loco translation files');
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('happyr.loco')->downloadAllTranslations();
        $output->writeln('Download complete');
    }
}
