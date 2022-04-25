<?php

namespace Daycry\ClassFinder\Libraries\ClassMap;

use Daycry\ClassFinder\Interfaces\FinderInterface;

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

        $matchingEntries = array_filter($classmapEntries, function(ClassmapEntry $entry) use ($namespace, $options) {
            return $entry->matches($namespace, $options);
        });

        return array_map(function(ClassmapEntry $entry) {
            return $entry->getClassName();
        }, $matchingEntries);
    }

    public function isNamespaceKnown($namespace)
    {
        $classmapEntries = $this->factory->getClassmapEntries();

        foreach($classmapEntries as $classmapEntry) {
            if ($classmapEntry->knowsNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }
}
