<?php

namespace PHPPM;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Closure;

class PPMConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $defaults = $this->getDefaults();
        
        $treeBuilder = new TreeBuilder('ppm');
        $treeBuilder->getRootNode()
            ->normalizeKeys(false)
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('bridge')
                    ->info('Bridge for converting React Psr7 requests to target framework')
                    ->defaultValue($defaults['bridge'])
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('host')
                    ->info('Load-Balancer host')
                    ->defaultValue($defaults['host'])
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('port')
                    ->info('Load-Balancer port')
                    ->min(0)->max(65535)
                    ->defaultValue($defaults['port'])
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue($defaults['port']))
                    ->end()
                ->end()
                ->integerNode('workers')
                    ->info('Worker count. Should be minimum equal to the number of CPU cores')
                    ->min(0)
                    ->defaultValue($defaults['workers'])
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue($defaults['workers']))
                    ->end()
                ->end()
                ->scalarNode('app-env')
                    ->info('The environment that your application will use to bootstrap (if any)')
                    ->defaultValue('dev')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('debug')
                    ->info('Enable/Disable debugging so that your application is more verbose, enables also hot-code reloading')
                    ->defaultFalse()
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('boolean'))
                    ->end()
                ->end()
                ->booleanNode('logging')
                    ->info('Enable/Disable http logging to stdout')
                    ->defaultTrue()
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('boolean'))
                    ->end()
                ->end()
                ->scalarNode('static-directory')
                    ->info('Static files root directory, if not provided static files will not be served')
                    ->defaultValue('')
                ->end()
                ->integerNode('max-requests')
                    ->info('Max requests per worker until it will be restarted')
                    ->min(0)
                    ->defaultValue($defaults['max-requests'])
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue($defaults['max-requests']))
                    ->end()
                ->end()
                ->integerNode('max-execution-time')
                    ->info('Maximum amount of time a request is allowed to execute before shutting down')
                    ->min(-1)
                    ->defaultValue(30)
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue(-1))
                    ->end()
                ->end()
                ->integerNode('memory-limit')
                    ->info('Maximum amount of memory a worker is allowed to consume (in MB) before shutting down')
                    ->min(-1)
                    ->defaultValue(-1)
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue(-1))
                    ->end()
                ->end()
                ->integerNode('ttl')
                    ->info('Time to live for a worker until it will be restarted')
                    ->min(-1)
                    ->defaultValue(300)
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue(-1))
                    ->end()
                ->end()
                ->booleanNode('populate-server-var')
                    ->info('If a worker application uses $_SERVER var it needs to be populated by request data')
                    ->defaultTrue()
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('boolean'))
                    ->end()
                ->end()
                ->scalarNode('bootstrap')
                    ->info('Class responsible for bootstrapping the application')
                    ->defaultValue('PHPPM\Bootstraps\Symfony')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('cgi-path')
                    ->info('Full path to the php-cgi executable')
                    ->defaultValue('')
                ->end()
                ->scalarNode('socket-path')
                    ->info('Path to a folder where socket files will be placed. Relative to working-directory or cwd()')
                    ->defaultValue('.ppm/run/')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('pidfile')
                    ->info('Path to a file where the pid of the master process is going to be stored')
                    ->defaultValue('.ppm/ppm.pid')
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('reload-timeout')
                    ->info('The number of seconds to wait before force closing a worker during a reload, or -1 to disable')
                    ->min(-1)
                    ->defaultValue(30)
                    ->beforeNormalization()
                        ->ifString()
                        ->then($this->castTo('integer'))
                    ->end()
                    ->validate()
                        ->ifEmpty()
                        ->then($this->setValue(-1))
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    public function getDefaults(): array
    {
        return [
            'bridge' => 'HttpKernel',
            'host' => '127.0.0.1',
            'port' => 8080,
            'workers' => max((int)`nproc`, 8),
            'app-env' => 'dev',
            'debug' => false,
            'logging' => true,
            'static-directory' => '',
            'max-requests' => 1000,
            'max-execution-time' => 30,
            'memory-limit' => -1,
            'ttl' => 300,
            'populate-server-var' => true,
            'bootstrap' => 'PHPPM\Bootstraps\Symfony',
            'cgi-path' => '',
            'socket-path' => '.ppm/run/',
            'pidfile' => '.ppm/ppm.pid',
            'reload-timeout' => 30,
        ];
    }

    protected function castTo(string $type): Closure
    {
        return function ($v) use ($type) {
            settype($v, $type);
            return $v;
        };
    }

    protected function setValue($v): Closure
    {
        return function () use ($v) {
            return $v;
        };
    }
}
