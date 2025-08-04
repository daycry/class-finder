<?php

namespace Daycry\ClassFinder\Libraries;

use Config\Autoload;

class BaseFactory
{
    private $composer;
    private ?Autoload $config                   = null;
    private ?array $classMapCache               = null;
    private ?array $psr4Cache                   = null;
    private ?array $autoloadConfigPsr4Cache     = null;
    private ?array $autoloadConfigClassMapCache = null;
    private ?array $autoloadConfigFilesCache    = null;

    public function __construct()
    {
        $this->composer = include COMPOSER_PATH;
    }

    private function getConfig(): Autoload
    {
        return $this->config ??= new Autoload();
    }

    protected function getClassMap(): array
    {
        return $this->classMapCache ??= $this->composer->getClassMap();
    }

    protected function getPSR4(): array
    {
        return $this->psr4Cache ??= $this->composer->getPrefixesPsr4();
    }

    protected function loadAutoloadConfigPsr4(): array
    {
        if ($this->autoloadConfigPsr4Cache !== null) {
            return $this->autoloadConfigPsr4Cache;
        }

        $namespaces = [];
        $config     = $this->getConfig();

        foreach ($config->psr4 as $psr4 => $dir) {
            $psr4              = rtrim($psr4, '\\') . '\\';
            $namespaces[$psr4] = [$dir];
        }

        return $this->autoloadConfigPsr4Cache = $namespaces;
    }

    protected function loadAutoloadConfigClassMap(): array
    {
        if ($this->autoloadConfigClassMapCache !== null) {
            return $this->autoloadConfigClassMapCache;
        }

        $config = $this->getConfig();

        return $this->autoloadConfigClassMapCache = $config->classmap;
    }

    protected function loadAutoloadConfigFiles(): array
    {
        if ($this->autoloadConfigFilesCache !== null) {
            return $this->autoloadConfigFilesCache;
        }

        helper('text');

        $files  = [];
        $config = $this->getConfig();

        foreach ($config->files as $file) {
            $files[random_string('alnum', 32)] = $file;
        }

        return $this->autoloadConfigFilesCache = $files;
    }
}
