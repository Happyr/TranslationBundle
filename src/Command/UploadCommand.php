<?php

namespace Happyr\TranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command will import your translations into your translator service
 */
class UploadCommand extends ContainerAwareCommand
{
    const RETURN_CODE_NO_FORCE = 2;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('happyr:translation:upload')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setDescription('Upload your translations into your translator service.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $this->getContainer()->get('happyr.translation')->uploadAllTranslations();
            $output->writeln('Upload complete');
        } else {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln(sprintf('<info>This is going to replace every translations on your translator service.</info>'));
            $output->writeln('Please run the operation with --force to execute');
            return self::RETURN_CODE_NO_FORCE;
        }
    }
}
