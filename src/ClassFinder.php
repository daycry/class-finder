<?php

namespace Daycry\ClassFinder;

use CodeIgniter\Config\BaseConfig;
use Daycry\ClassFinder\Config\ClassFinder as ClassFinderConfig;
use Daycry\ClassFinder\Interfaces\FinderInterface;
use Exception;

class ClassFinder
{
    public const STANDARD_MODE    = 1;
    public const RECURSIVE_MODE   = 2;
    public const ALLOW_CLASSES    = 4;
    public const ALLOW_INTERFACES = 8;
    public const ALLOW_TRAITS     = 16;
    public const ALLOW_FUNCTIONS  = 32;
    public const ALLOW_ALL        = 60;

    private array $finders                           = [];
    private static ?ClassFinderConfig $defaultConfig = null;
    private array $namespaceCache                    = [];

    public function __construct(?BaseConfig $config = null)
    {
        $this->initialize($config);
    }

    private function initialize(?BaseConfig $config = null): void
    {
        if ($config === null) {
            $config = self::$defaultConfig ??= config('ClassFinder');
        }

        foreach ($config->finder as $method => $value) {
            if ($value === true && isset($config->finderClass[$method])) {
                $finderClass = $config->finderClass[$method];
                if (class_exists($finderClass)) {
                    $class = new $finderClass();
                    if ($class instanceof FinderInterface) {
                        $this->finders[] = $class;
                    }
                }
            }
        }

        // Sort finders by priority (lower number = higher priority)
        usort($this->finders, static fn (FinderInterface $a, FinderInterface $b) => $a->getPriority() <=> $b->getPriority());
    }

    /**
     * Identify classes in a given namespace.
     *
     * @return list<string>
     *
     * @throws Exception
     */
    public function getClassesInNamespace(string $namespace, int $options = self::STANDARD_MODE): array
    {
        if (! ($options & (self::ALLOW_INTERFACES | self::ALLOW_TRAITS))) {
            $options |= self::ALLOW_CLASSES;
        }

        $cacheKey = $namespace . '_' . $options;
        if (isset($this->namespaceCache[$cacheKey])) {
            return $this->namespaceCache[$cacheKey];
        }

        $findersWithNamespace = $this->findersWithNamespace($namespace);

        $classes = [];

        foreach ($findersWithNamespace as $finder) {
            $classes = array_merge($classes, $finder->findClasses($namespace, $options));
        }

        $result                          = array_unique($classes);
        $this->namespaceCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * @return list<FinderInterface>
     */
    private function findersWithNamespace(string $namespace): array
    {
        return array_filter($this->finders, static fn (FinderInterface $finder) => $finder->isNamespaceKnown($namespace));
    }
}
