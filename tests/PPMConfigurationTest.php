<?php

namespace PHPPM\Tests;

use PHPPM\PPMConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\BooleanNode;
use Symfony\Component\Config\Definition\FloatNode;
use Symfony\Component\Config\Definition\IntegerNode;
use Symfony\Component\Config\Definition\VariableNode;

class PPMConfigurationTest extends TestCase
{
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new PPMConfiguration();
    }

    public function provideConfigVars(): iterable
    {
        yield ['bridge', 'string'];
        yield ['host', 'string'];
        yield ['port', 'integer'];
        yield ['workers', 'integer'];
        yield ['app-env', 'string'];
        yield ['debug', 'boolean'];
        yield ['logging', 'boolean'];
        yield ['static-directory', 'string'];
        yield ['max-requests', 'integer'];
        yield ['max-execution-time', 'integer'];
        yield ['memory-limit', 'integer'];
        yield ['ttl', 'integer'];
        yield ['populate-server-var', 'boolean'];
        yield ['bootstrap', 'string'];
        yield ['cgi-path', 'string'];
        yield ['socket-path', 'string'];
        yield ['pidfile', 'string'];
        yield ['reload-timeout', 'integer'];
    }

    public function testGetConfigTreeBuilder(): array
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $this->assertInstanceOf(ArrayNode::class, $tree);
        return $tree->getChildren();
    }

    public function testGetConfigTree(): array
    {
        $tree = $this->configuration->getConfigTree();
        $this->assertInstanceOf(ArrayNode::class, $tree);
        return $tree->getChildren();
    }

    /**
     * @dataProvider provideConfigVars
     * @depends testGetConfigTree
     */
    public function testConfigNodes(string $key, string $type, array $nodes)
    {
        $this->assertArrayHasKey($key, $nodes);
        switch ($type) {
            case 'array':
                $this->assertInstanceOf(ArrayNode::class, $nodes[$key]);
                break;
            case 'boolean':
                $this->assertInstanceOf(BooleanNode::class, $nodes[$key]);
                break;
            case 'float':
                $this->assertInstanceOf(FloatNode::class, $nodes[$key]);
                break;
            case 'integer':
                $this->assertInstanceOf(IntegerNode::class, $nodes[$key]);
                break;
            default:
                $this->assertInstanceOf(VariableNode::class, $nodes[$key]);
                break;
        }
        $this->assertTrue($nodes[$key]->hasDefaultValue(), "Option '{$key}' has no default value");
    }

    public function testGetDefaults(): array
    {
        $defaults = $this->configuration->getDefaults();
        $this->assertNotEmpty($defaults);
        return $defaults;
    }

    /**
     * @dataProvider provideConfigVars
     * @depends testGetDefaults
     */
    public function testDefaultVars(string $key, string $type, array $defaults)
    {
        $this->assertArrayHasKey($key, $defaults);
        $this->assertInternalType($type, $defaults[$key]);
    }
}
