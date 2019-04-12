<?php

namespace PHPPM\Commands;

use PHPPM\PPMConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

trait ConfigTrait
{
    protected $file = './ppm.json';
    protected $configTree;

    protected function configurePPMOptions(Command $command)
    {
        $this->configTree = (new PPMConfiguration())->getConfigTreeBuilder()->buildTree();

        foreach ($this->configTree->getChildren() as $node) {
            $command->addOption(
                $node->getName(),
                null,
                InputOption::VALUE_REQUIRED,
                $node->getInfo(),
                $node->hasDefaultValue() ? $node->getDefaultValue() : null
            );
        }

        $command->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file', '');
    }

    protected function renderConfig(OutputInterface $output, array $config)
    {
        $table = new Table($output);
        $table->setHeaders(['Option', 'Value', 'Default']);

        foreach ($this->configTree->getChildren() as $node) {
            $name = $node->getName();
            $value = isset($config[$name]) ? $config[$name] : null;
            $default = $node->hasDefaultValue() ? $node->getDefaultValue() : null;

            $row = [
                $name,
                $value === null ? '' : var_export($value, true),
                $default === null ? '' : var_export($default, true),
            ];

            if ($value !== $default) {
                $row = array_map(function ($v) {
                    return "<comment>{$v}</comment>";
                }, $row);
            }

            $table->addRow($row);
        }

        $table->render();
    }

    /**
     * @param InputInterface $input
     * @param bool $create
     * @return string
     * @throws \Exception
     */
    protected function getConfigPath(InputInterface $input, $create = false)
    {
        $configOption = $input->getOption('config');
        if ($configOption && !file_exists($configOption)) {
            if ($create) {
                file_put_contents($configOption, json_encode([]));
            } else {
                throw new \Exception(sprintf('Config file not found: "%s"', $configOption));
            }
        }
        $possiblePaths = [
            $configOption,
            $this->file,
            sprintf('%s/%s', dirname($GLOBALS['argv'][0]), $this->file)
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }
        return '';
    }

    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        if ($path = $this->getConfigPath($input)) {
            $content = file_get_contents($path);
            $config = json_decode($content, true);
        }

        $options = $this->getSpecifiedOptions($input);

        $config = (new Processor())->process(
            $this->configTree,
            [$config, $options]
        );

        if ('' === $config['cgi-path']) {
            //not set in config nor in command options -> autodetect path
            $executableFinder = new PhpExecutableFinder();
            $binary = $executableFinder->find();

            $cgiPaths = [
                $binary . '-cgi', //php7.0 -> php7.0-cgi
                str_replace('php', 'php-cgi', $binary), //php7.0 => php-cgi7.0
            ];

            foreach ($cgiPaths as $cgiPath) {
                $path = trim(`which $cgiPath`);
                if ($path) {
                    $config['cgi-path'] = $path;
                    break;
                }
            }

            if ('' === $config['cgi-path']) {
                $output->writeln('<error>PPM could find a php-cgi path. Please specify by --cgi-path=</error>');
                exit(1);
            }
        }

        return $config;
    }

    protected function getSpecifiedOptions(InputInterface $input): array
    {
        $options = $input->getOptions();
        return array_filter($options, function (string $name) use ($input) {
            return $input->hasParameterOption("--{$name}");
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $render
     * @return array|mixed
     */
    protected function initializeConfig(InputInterface $input, OutputInterface $output, $render = true)
    {
        if ($workingDir = $input->getArgument('working-directory')) {
            chdir($workingDir);
        }
        $config = $this->loadConfig($input, $output);

        if ($path = $this->getConfigPath($input)) {
            $modified = '';
            $fileConfig = json_decode(file_get_contents($path), true);
            if (json_encode($fileConfig) !== json_encode($config)) {
                $modified = ', modified by command arguments';
            }
            $output->writeln(sprintf('<info>Read configuration %s%s.</info>', $path, $modified));
        }
        $output->writeln(sprintf('<info>%s</info>', getcwd()));

        if ($render) {
            $this->renderConfig($output, $config);
        }
        return $config;
    }
}
