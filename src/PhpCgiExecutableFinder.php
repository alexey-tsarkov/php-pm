<?php

namespace PHPPM;

use Symfony\Component\Process\ExecutableFinder;

class PhpCgiExecutableFinder
{
    private $executableFinder;

    public function __construct()
    {
        $this->executableFinder = new ExecutableFinder();
    }

    /**
     * @return string|false
     */
    public function find()
    {
        $dirs = [dirname(PHP_BINARY)];
        $name = basename(PHP_BINARY);

        $binary = $this->executableFinder->find($name . '-cgi', false, $dirs);
        if (!$binary) {
            $binary = $this->executableFinder->find(strtr($name, ['php' => 'php-cgi']), false, $dirs);
        }
        if (!$binary) {
            $binary = $this->executableFinder->find('php-cgi', false, $dirs);
        }

        return $binary;
    }
}
