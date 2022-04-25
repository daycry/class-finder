<?php

namespace Daycry\ClassFinder\Libraries;

class BaseFactory
{
    private $composer;

    public function __construct()
    {
        $this->composer = include COMPOSER_PATH;
    }
    public function getClassMap()
    {
        return $this->composer->getClassMap();
    }

    public function getPSR4()
    {
        return $this->composer->getPrefixesPsr4();
    }
}
