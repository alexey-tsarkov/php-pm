<?php

namespace PHPPM\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command
{
    use ConfigTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('config')
            ->addOption('show-option', null, InputOption::VALUE_REQUIRED, 'Instead of writing the config, only show the given option.', '')
            ->setDescription("Configures config file, default - \"{$this->configFile}\"");

        $this->configurePPMOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->locateConfigPath($input, true);
        $config = $this->loadConfig($input, $output);

        if ($name = $input->getOption('show-option')) {
            if (isset($config[$name])) {
                $output->writeln(var_export($config[$name], true));
            }
            return;
        }

        $this->renderConfig($output, $config);

        $configPath = $this->configPath ?: $this->configFile;
        $newContent = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if (file_exists($configPath) && $newContent === file_get_contents($configPath)) {
            $configPath = realpath($configPath);
            $output->writeln("No changes to \"{$configPath}\" file.");
        } else {
            file_put_contents($configPath, $newContent);
            $configPath = realpath($configPath);
            $output->writeln("<info>\"${configPath}\" file written.</info>");
        }
    }
}
