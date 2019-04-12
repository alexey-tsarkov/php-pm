<?php

namespace PHPPM\Commands;

use PHPPM\PPMConfiguration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;

trait ConfigTrait
{
    protected $configFile = 'ppm.json';
    protected $configPath = '';
    protected $configTree;

    protected function configurePPMOptions(Command $command, string ...$options)
    {
        $this->configTree = (new PPMConfiguration())->getConfigTreeBuilder()->buildTree();

        $configNodes = $this->configTree->getChildren();
        $options = $options ?: array_keys($configNodes);

        foreach ($options as $name) {
            if (!isset($configNodes[$name])) {
                continue;
            }
            $node = $configNodes[$name];
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
     * @throws FileLocatorFileNotFoundException
     */
    protected function locateConfigPath(InputInterface $input, bool $create = false): string
    {
        if ($this->configPath) {
            return $this->configPath;
        }

        if ($configOption = $input->getOption('config')) {
            if (file_exists($configOption)) {
                return $this->configPath = realpath($configOption);
            }
   
            if ($create) {
                file_put_contents($configOption, json_encode(new \stdClass));
                return $this->configPath = realpath($configOption);
            }

            throw new FileLocatorFileNotFoundException("The file \"{$configOption}\" does not exist.");
        }

        $locator = new FileLocator([
            getcwd(),
            realpath(dirname($GLOBALS['argv'][0])),
        ]);

        try {
            $this->configPath = $locator->locate($this->configFile, null, true);
        } catch (FileLocatorFileNotFoundException $ex) {
            $this->configPath = '';
        }

        return $this->configPath;
    }

    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        if ($this->locateConfigPath($input)) {
            $content = file_get_contents($this->configPath);
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
    protected function initializeConfig(InputInterface $input, OutputInterface $output, bool $render = true)
    {
        if ($workingDir = $input->getArgument('working-directory')) {
            chdir($workingDir);
        }
        $config = $this->loadConfig($input, $output);

        if ($this->configPath) {
            $modified = '';
            $fileConfig = json_decode(file_get_contents($this->configPath), true);
            if (json_encode($fileConfig) !== json_encode($config)) {
                $modified = ', modified by command arguments';
            }
            $output->writeln(sprintf('<info>Read configuration "%s"%s.</info>', $this->configPath, $modified));
        }
        $output->writeln(sprintf('<info>Working directory "%s".</info>', getcwd()));

        if ($render) {
            $this->renderConfig($output, $config);
        }
        return $config;
    }
}
