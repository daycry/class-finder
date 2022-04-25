<?php

namespace Daycry\ClassFinder;


use Daycry\ClassFinder\Interfaces\FinderInterface;
use Daycry\ClassFinder\Libraries\Classmap\ClassmapEntryFactory;
use Daycry\ClassFinder\Libraries\Classmap\ClassmapFinder;
use Daycry\ClassFinder\Libraries\Files\FilesEntryFactory;
use Daycry\ClassFinder\Libraries\Files\FilesFinder;
use Daycry\ClassFinder\Libraries\PSR4\PSR4Finder;
use Daycry\ClassFinder\Libraries\PSR4\PSR4NamespaceFactory;

class ClassFinder
{
    const STANDARD_MODE = 1;
    const RECURSIVE_MODE = 2;

    private array $_finders = [];

    public function __construct()
    {
        $this->initialize();
    }
    /**
     * @return void
     */
    private function initialize()
    {
        $config = config('ClassFinder');

        foreach( $config->finder as $method => $value )
        {
            if( $value === true && isset( $config->finderClass[$method] ) )
            {
                $class = new $config->finderClass[$method]();
                if( $class instanceof \Daycry\ClassFinder\Interfaces\FinderInterface )
                {
                    array_push( $this->_finders, $class );
                }
            }
        }

        /*if (!(self::$psr4 instanceof PSR4Finder)) {
            $PSR4Factory = new PSR4NamespaceFactory();
            self::$psr4 = new PSR4Finder($PSR4Factory);
        }

        if (!(self::$classmap instanceof ClassmapFinder)) {
            $classmapFactory = new ClassmapEntryFactory();
            self::$classmap = new ClassmapFinder($classmapFactory);
        }

        if (!(self::$files instanceof FilesFinder) && self::$useFilesSupport) {
            $filesFactory = new FilesEntryFactory(self::$config);
            self::$files = new FilesFinder($filesFactory);
        }*/
    }

    /**
     * Identify classes in a given namespace.
     *
     * @param string $namespace
     * @param int $options
     * @return string[]
     *
     * @throws \Exception
     */
    public function getClassesInNamespace($namespace, $options = self::STANDARD_MODE)
    {
        $this->initialize();

        $findersWithNamespace = $this->_findersWithNamespace($namespace);

        $classes = array_reduce($findersWithNamespace, function($carry, FinderInterface $finder) use ($namespace, $options){
            return array_merge($carry, $finder->findClasses($namespace, $options));
        }, array());


        return array_unique($classes);
    }

    /**
     * @param string $namespace
     * @return FinderInterface[]
     */
    private function _findersWithNamespace($namespace)
    {
        $findersWithNamespace = array_filter($this->_finders, function (FinderInterface $finder) use ($namespace) {
            return $finder->isNamespaceKnown($namespace);
        });

        return $findersWithNamespace;
    }
}
