<?php

namespace Daycry\ClassFinder\Libraries\ClassMap;

use Daycry\ClassFinder\Interfaces\FinderInterface;
use Daycry\ClassFinder\ClassFinder;

class ClassMapFinder implements FinderInterface
{
    private ClassMapFactory $factory;

    public function __construct()
    {
        $this->factory = new ClassMapFactory();
    }

    /**
     * @param string $namespace
     * @param int $options
     * @return string[]
     */
    public function findClasses($namespace, $options)
    {
        $classmapEntries = $this->factory->getClassmapEntries();

        $matchingEntries = array_filter($classmapEntries, function (ClassmapEntry $entry) use ($namespace, $options) {
            if (!$entry->matches($namespace, $options)) {
                return false;
            }

            $potentialClass = $entry->getClassName();
            if (function_exists($potentialClass)) {
                // For some reason calling class_exists() on a namespace'd function raises a Fatal Error (tested PHP 7.0.8)
                // Example: DeepCopy\deep_copy
                return $options & ClassFinder::ALLOW_FUNCTIONS;
            } elseif (class_exists($potentialClass)) {
                return $options & ClassFinder::ALLOW_CLASSES;
            } elseif (interface_exists($potentialClass, false)) {
                return $options & ClassFinder::ALLOW_INTERFACES;
            } elseif (trait_exists($potentialClass, false)) {
                return $options & ClassFinder::ALLOW_TRAITS;
            }
        });

        return array_map(function (ClassmapEntry $entry) {
            return $entry->getClassName();
        }, $matchingEntries);
    }

    public function isNamespaceKnown($namespace)
    {
        $classmapEntries = $this->factory->getClassmapEntries();

        foreach ($classmapEntries as $classmapEntry) {
            if ($classmapEntry->knowsNamespace($namespace)) {
                return true;
            }
        }

        // @codeCoverageIgnoreStart
        return false;
        // @codeCoverageIgnoreEnd
    }
}
