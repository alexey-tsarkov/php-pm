<?php

namespace PHPPM\Tests;

use PHPPM\PhpCgiExecutableFinder;
use PHPUnit\Framework\TestCase;

class PhpCgiExecutableFinderTest extends TestCase
{
    protected $finder;

    protected function setUp()
    {
        $this->finder = new PhpCgiExecutableFinder();
    }

    public function testFind()
    {
        $binary = $this->finder->find();

        $this->assertNotEmpty($binary, 'Ensure php-cgi installed');
        $this->assertFileExists($binary);
        $this->assertTrue(is_executable($binary), "File '{$binary}' not executable");
        $this->assertRegExp('/^PHP \d+\.\d+\.\d+\S* \(cgi-fcgi\)/U', `"{$binary}" -v`, "Unexpected 'php-cgi -v' output");
    }
}
