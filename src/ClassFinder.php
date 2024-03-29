<?php

namespace Daycry\ClassFinder;

use Daycry\ClassFinder\Interfaces\FinderInterface;
use CodeIgniter\Config\BaseConfig;

class ClassFinder
{
    public const STANDARD_MODE = 1;
    public const RECURSIVE_MODE = 2;

    public const ALLOW_CLASSES = 4;
    public const ALLOW_INTERFACES = 8;
    public const ALLOW_TRAITS = 16;
    public const ALLOW_FUNCTIONS = 32;

    public const ALLOW_ALL = 60;

    private array $_finders = [];

    public function __construct(BaseConfig $config = null)
    {
        $this->initialize($config);
    }
    /**
     * @return void
     */
    private function initialize(BaseConfig $config = null)
    {
        if ($config === null) {
            $config = config('ClassFinder');
        }

        foreach ($config->finder as $method => $value) {
            if ($value === true && isset($config->finderClass[$method])) {
                $class = new $config->finderClass[$method]();
                if ($class instanceof \Daycry\ClassFinder\Interfaces\FinderInterface) {
                    array_push($this->_finders, $class);
                }
            }
        }
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
        if (!($options & (self::ALLOW_INTERFACES | self::ALLOW_TRAITS))) {
            $options |= self::ALLOW_CLASSES;
        }

        $findersWithNamespace = $this->_findersWithNamespace($namespace);

        $classes = array_reduce($findersWithNamespace, function ($carry, FinderInterface $finder) use ($namespace, $options) {
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
