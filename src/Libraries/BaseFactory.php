<?php

namespace Daycry\ClassFinder\Libraries;
use Config\Autoload;

class BaseFactory
{
    private $composer;
    private Autoload $config;
    
    public function __construct()
    {
        $this->composer = include COMPOSER_PATH;
        $this->config = new Autoload();
    }
    protected function getClassMap() :array
    {
        return $this->composer->getClassMap();
    }

    protected function getPSR4() :array
    {
        return $this->composer->getPrefixesPsr4();
    }

    protected function loadAutoloadConfigPsr4() :array
    {
        $namespaces = [];
        foreach( $this->config->psr4 as $psr4 => $dir )
        {
            if( substr($psr4, -1) != '\\' )
            {
                $psr4 = $psr4 . '\\';
            }

            $namespaces[$psr4] = array($dir);
        }

        return $namespaces;
    }

    protected function loadAutoloadConfigClassMap() :array
    {
        $classmap = [];
        foreach( $this->config->classmap as $c => $dir )
        {
            $classmap[$c] = $dir;
        }

        return $classmap;
    }

    protected function loadAutoloadConfigFiles() :array
    {
        helper('text');

        $files = [];
        foreach( $this->config->files as $file )
        {
            $files[random_string('alnum', 32)] = $file;
        }

        return $files;
    }
}
