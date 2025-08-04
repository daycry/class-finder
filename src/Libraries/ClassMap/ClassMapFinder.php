<?php

namespace Daycry\ClassFinder\Libraries\ClassMap;

use Daycry\ClassFinder\ClassFinder;
use Daycry\ClassFinder\Interfaces\FinderInterface;

class ClassMapFinder implements FinderInterface
{
    private ClassMapFactory $factory;

    public function __construct()
    {
        $this->factory = new ClassMapFactory();
    }

    public function getPriority(): int
    {
        return 2; // Medium priority for ClassMap
    }

    /**
     * @return list<string>
     */
    public function findClasses(string $namespace, int $options): array
    {
        $classmapEntries = $this->factory->getClassmapEntries();

        $matchingEntries = array_filter($classmapEntries, static function (ClassmapEntry $entry) use ($namespace, $options) {
            if (! $entry->matches($namespace, $options)) {
                return false;
            }

            $potentialClass = $entry->getClassName();
            if (function_exists($potentialClass)) {
                // For some reason calling class_exists() on a namespace'd function raises a Fatal Error (tested PHP 7.0.8)
                // Example: DeepCopy\deep_copy
                return $options & ClassFinder::ALLOW_FUNCTIONS;
            }
            if (class_exists($potentialClass)) {
                return $options & ClassFinder::ALLOW_CLASSES;
            }
            if (interface_exists($potentialClass, false)) {
                return $options & ClassFinder::ALLOW_INTERFACES;
            }
            if (trait_exists($potentialClass, false)) {
                return $options & ClassFinder::ALLOW_TRAITS;
            }
        });

        return array_map(static fn (ClassmapEntry $entry) => $entry->getClassName(), $matchingEntries);
    }

    public function isNamespaceKnown(string $namespace): bool
    {
        $classmapEntries = $this->factory->getClassmapEntries();

        foreach ($classmapEntries as $classmapEntry) {
            if ($classmapEntry->knowsNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }
}
