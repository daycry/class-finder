<?php

namespace Daycry\ClassFinder\Libraries\ClassMap;

use Daycry\ClassFinder\Libraries\BaseFactory;

class ClassMapFactory extends BaseFactory
{
    private ?array $classmapEntriesCache = null;

    /**
     * @return list<ClassMapEntry>
     */
    public function getClassMapEntries(): array
    {
        if ($this->classmapEntriesCache !== null) {
            return $this->classmapEntriesCache;
        }

        $classmap = array_merge($this->getClassMap(), $this->loadAutoloadConfigClassMap());

        // if classmap has no entries return empty array
        if (empty($classmap)) {
            return $this->classmapEntriesCache = [];
        }

        $classmapEntries = [];

        foreach (array_keys($classmap) as $className) {
            $classmapEntries[] = new ClassMapEntry($className);
        }

        return $this->classmapEntriesCache = $classmapEntries;
    }
}
