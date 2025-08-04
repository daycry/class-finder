<?php

namespace Daycry\ClassFinder\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\ClassFinder\Libraries\ClassMap\ClassMapFinder;
use Daycry\ClassFinder\Libraries\Files\FilesFinder;
use Daycry\ClassFinder\Libraries\PSR4\PSR4Finder;

class ClassFinder extends BaseConfig
{
    public array $finder = [
        'PSR4'     => true,
        'classMap' => true,
        'files'    => true,
    ];
    public array $finderClass = [
        'PSR4'     => PSR4Finder::class,
        'classMap' => ClassMapFinder::class,
        'files'    => FilesFinder::class,
    ];

    /**
     * Enable/disable specific finders
     */
    public function enableFinder(string $finder): void
    {
        if (isset($this->finder[$finder])) {
            $this->finder[$finder] = true;
        }
    }

    public function disableFinder(string $finder): void
    {
        if (isset($this->finder[$finder])) {
            $this->finder[$finder] = false;
        }
    }

    /**
     * Get enabled finders
     */
    public function getEnabledFinders(): array
    {
        return array_filter($this->finder, static fn ($enabled) => $enabled === true);
    }

    /**
     * Validate configuration
     */
    public function isValid(): bool
    {
        foreach ($this->finderClass as $finder => $class) {
            if (! class_exists($class)) {
                return false;
            }
        }

        return true;
    }
}
