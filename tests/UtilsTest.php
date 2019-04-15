<?php

namespace PHPPM\Tests;

use PHPPM\Utils;

class UtilsTest extends PhpPmTestCase
{
    public function providePaths()
    {
        return [
            ['/images/foo.png', '/images/foo.png'],
            ['/images/../foo.png', '/foo.png'],
            ['/images/sub/../../foo.png', '/foo.png'],
            ['/images/sub/../foo.png', '/images/foo.png'],

            ['/../foo.png', false],
            ['../foo.png', false],
            ['//images/d/../../foo.png', '/foo.png'],
            ['/images//../foo.png', '/images/foo.png'],
            ["/images/\0/../foo.png", '/images/foo.png'],
            ["/images/\0../foo.png", '/foo.png'],
        ];
    }

    /**
     * @dataProvider providePaths
     * @param string $path
     * @param string $expected
     */
    public function testParseQueryPath($path, $expected)
    {
        $this->assertEquals($expected, Utils::parseQueryPath($path));
    }

    public function testHijackProperty()
    {
        $object = new \PHPPM\SlavePool();
        Utils::hijackProperty($object, 'slaves', ['SOME VALUE']);

        $r = new \ReflectionObject($object);
        $p = $r->getProperty('slaves');
        $p->setAccessible(true);
        $this->assertEquals(['SOME VALUE'], $p->getValue($object));
    }

    public function testGetMaxMemory()
    {
        $mem = Utils::getMaxMemory();
        $this->assertGreaterThan(0, $mem);
    }

    public function testGetNumberOfProcessors()
    {
        $nproc = Utils::getNumberOfProcessors();
        $this->assertGreaterThan(0, $nproc);
    }
}
