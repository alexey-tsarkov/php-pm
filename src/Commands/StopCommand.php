<?php

namespace PHPPM\Commands;

use PHPPM\ProcessClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    use ConfigTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('stop')
            ->setDescription('Stops the server')
            ->addWorkingDirectoryArgument()
            ->addConfigOption()
            ->addPPMOptions('socket-path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->initializeConfig($input, $output, false);

        $handler = new ProcessClient();
        $handler->setSocketPath($config['socket-path']);

        $handler->stopProcessManager(function ($status) use ($output) {
            $output->writeln('Requested process manager to stop.');
        });
    }
}
