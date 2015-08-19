<?php

namespace Happyr\LocoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Once you have downloaded all the translations you may want to synchronize with the progress from the translators.
 * This command will keep your placeholders for missing translations.
 */
class SynchronizeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:loco:sync')
            ->setDescription('Sync all your translations with Loco. Leave place holders for missing translations.');
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('happyr.loco')->synchronizeAllTranslations();
        $output->writeln('Synchronization complete');
    }
}
