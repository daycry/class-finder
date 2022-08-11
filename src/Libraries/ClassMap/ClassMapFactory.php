<?php

namespace Daycry\ClassFinder\Libraries\ClassMap;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Daycry\ClassFinder\Libraries\PSR4\PSR4Namespace;

class ClassMapFactory extends \Daycry\ClassFinder\Libraries\BaseFactory
{
    /**
     * @return string[]
     */
    public function getClassMapEntries()
    {
        $classmap = $this->getClassMap();

        // if classmap has no entries return empty array
        if(count($classmap) == 0) {
            // @codeCoverageIgnoreStart
            return array();
            // @codeCoverageIgnoreEnd
        }

        $classmapKeys = array_keys($classmap);

        return array_map(function($index) use ($classmapKeys){
            return new ClassMapEntry($classmapKeys[$index]);
        }, range(0, count($classmap) - 1));
    }
}