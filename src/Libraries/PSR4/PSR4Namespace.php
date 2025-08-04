<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\ClassFinder;

class PSR4Namespace
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var list<string>
     */
    private $directories;

    /**
     * @var list<PSR4Namespace>
     */
    private $directSubnamespaces;

    /**
     * @param string       $namespace
     * @param list<string> $directories
     */
    public function __construct($namespace, $directories)
    {
        $this->namespace   = $namespace;
        $this->directories = $directories;
    }

    /**
     * @param string $namespace
     *
     * @return bool
     */
    public function knowsNamespace($namespace)
    {
        $numberOfSegments = count(explode('\\', $namespace));
        $matchingSegments = $this->countMatchingNamespaceSegments($namespace);

        if ($matchingSegments === 0) {
            // Provided namespace doesn't map to anything registered.
            return false;
        }
        if ($numberOfSegments <= $matchingSegments) {
            // This namespace is a superset of the provided namespace. Namespace is known.
            return true;
        }
        // This namespace is a subset of the provided namespace. We must resolve the remaining segments to a directory.
        $relativePath = substr($namespace, strlen($this->namespace));

        foreach ($this->directories as $directory) {
            $path = $this->normalizePath($directory, $relativePath);
            if (is_dir($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines how many namespace segments match the internal namespace. This is useful because multiple namespaces
     * may technically match a registered namespace root, but one of the matches may be a better match. Namespaces that
     * match, but are not _the best_ match are incorrect matches. TestApp1\\ is **not** the best match when searching for
     * namespace TestApp1\\Multi\\Foo if TestApp1\\Multi was explicitly registered.
     *
     * PSR4Namespace $a;
     * $a->namespace = "TestApp1\\";
     * $a->countMatchingNamespaceSegments("TestApp1\\Multi") -> 1, TestApp1 matches.
     *
     * PSR4Namespace $b;
     * $b->namespace = "TestApp1\\Multi";
     * $b->countMatchingNamespaceSegments("TestApp1\\Multi") -> 2, TestApp1\\Multi matches
     *
     * PSR4Namespace $c;
     * $c->namespace = "HaydenPierce\\Foo\\Bar";
     * $c->countMatchingNamespaceSegments("TestApp1\\Multi") -> 0, No matches.
     *
     * @param string $namespace
     *
     * @return int
     */
    public function countMatchingNamespaceSegments($namespace)
    {
        $namespaceFragments          = explode('\\', $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';

            if (str_contains($this->namespace, $possibleNamespace)) {
                return count(explode('\\', $possibleNamespace)) - 1;
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));
        }

        return 0;
    }

    /**
     * @param string $namespace
     *
     * @return bool
     */
    public function isAcceptableNamespace($namespace)
    {
        $namespaceSegments = count(explode('\\', $this->namespace)) - 1;
        $matchingSegments  = $this->countMatchingNamespaceSegments($namespace);

        return $namespaceSegments === $matchingSegments;
    }

    /**
     * @param string $namespace
     *
     * @return bool
     */
    public function isAcceptableNamespaceRecursiveMode($namespace)
    {
        // Remove prefix backslash (TODO: review if we do this eariler).
        $namespace = ltrim($namespace, '\\');

        return str_starts_with($this->namespace, $namespace);
    }

    /**
     * Used to identify subnamespaces.
     *
     * @return list<string>
     */
    public function findDirectories()
    {
        $self        = $this;
        $directories = array_reduce($this->directories, static function ($carry, $directory) use ($self) {
            $path          = $self->normalizePath($directory, '');
            $realDirectory = realpath($path);
            if ($realDirectory !== false) {
                return array_merge($carry, [$realDirectory]);
            }

            return $carry;
        }, []);

        $arraysOfClasses = array_map(static function ($directory) use ($self) {
            $files = scandir($directory);

            return array_map(static fn ($file) => $self->normalizePath($directory, $file), $files);
        }, $directories);

        $potentialDirectories = array_reduce($arraysOfClasses, static fn ($carry, $arrayOfClasses) => array_merge($carry, $arrayOfClasses), []);

        // Remove '.' and '..' directories
        $potentialDirectories = array_filter($potentialDirectories, static function ($potentialDirectory) {
            $segments    = explode('/', $potentialDirectory);
            $lastSegment = array_pop($segments);

            return $lastSegment !== '.' && $lastSegment !== '..';
        });

        $confirmedDirectories = array_filter($potentialDirectories, static fn ($potentialDirectory) => is_dir($potentialDirectory));

        return $confirmedDirectories;
    }

    /**
     * @param string $namespace
     * @param int    $options
     *
     * @return list<string>
     */
    public function findClasses($namespace, $options = ClassFinder::STANDARD_MODE)
    {
        $relativePath = substr($namespace, strlen($this->namespace));

        $self = $this;

        $directories = array_reduce($this->directories, static function ($carry, $directory) use ($relativePath, $self) {
            $path          = $self->normalizePath($directory, $relativePath);
            $realDirectory = realpath($path);

            if ($realDirectory !== false) {
                return array_merge($carry, [$realDirectory]);
            }

            return $carry;
        }, []);

        $arraysOfClasses = array_map(static fn ($directory) => scandir($directory), $directories);

        $potentialClassFiles = array_reduce($arraysOfClasses, static fn ($carry, $arrayOfClasses) => array_merge($carry, $arrayOfClasses), []);

        $potentialClasses = array_map(static fn ($file) => $namespace . '\\' . str_replace('.php', '', $file), $potentialClassFiles);

        if ($options & ClassFinder::RECURSIVE_MODE) {
            return $this->getClassesFromListRecursively($namespace, $options);
        }

        return array_filter($potentialClasses, static function ($potentialClass) use ($options) {
            if (! str_contains($potentialClass, 'Views')) {
                if (function_exists($potentialClass)) {
                    // For some reason calling class_exists() on a namespace'd function raises a Fatal Error (tested PHP 7.0.8)
                    // Example: DeepCopy\deep_copy
                    // return false;
                    return $options & ClassFinder::ALLOW_FUNCTIONS;
                }

                // return class_exists($potentialClass);
                return ($options & ClassFinder::ALLOW_CLASSES && class_exists($potentialClass))
                    || ($options & ClassFinder::ALLOW_INTERFACES && interface_exists($potentialClass, false))
                    || ($options & ClassFinder::ALLOW_TRAITS && trait_exists($potentialClass, false));
            }

            return false;
        });
    }

    /**
     * @param mixed $options
     *
     * @return list<string>
     */
    private function getDirectClassesOnly($options)
    {
        $self        = $this;
        $directories = array_reduce($this->directories, static function ($carry, $directory) use ($self) {
            $path          = $self->normalizePath($directory, '');
            $realDirectory = realpath($path);
            if ($realDirectory !== false) {
                return array_merge($carry, [$realDirectory]);
            }

            return $carry;
        }, []);

        $arraysOfClasses = array_map(static fn ($directory) => scandir($directory), $directories);

        $potentialClassFiles = array_reduce($arraysOfClasses, static fn ($carry, $arrayOfClasses) => array_merge($carry, $arrayOfClasses), []);

        $selfNamespace    = $this->namespace; // PHP 5.3 BC
        $potentialClasses = array_map(static fn ($file) => $selfNamespace . str_replace('.php', '', $file), $potentialClassFiles);

        return array_filter($potentialClasses, static function ($potentialClass) use ($options) {
            if (! str_contains($potentialClass, 'Views')) {
                if (function_exists($potentialClass)) {
                    // For some reason calling class_exists() on a namespace'd function raises a Fatal Error (tested PHP 7.0.8)
                    // Example: DeepCopy\deep_copy
                    // return false;
                    return $options & ClassFinder::ALLOW_FUNCTIONS;
                }

                // return class_exists($potentialClass);
                return ($options & ClassFinder::ALLOW_CLASSES && class_exists($potentialClass))
                    || ($options & ClassFinder::ALLOW_INTERFACES && interface_exists($potentialClass, false))
                    || ($options & ClassFinder::ALLOW_TRAITS && trait_exists($potentialClass, false));
            }

            return false;
        });
    }

    /**
     * The views folder does not contain classes and is excluded.
     *
     * @param string $namespace
     * @param mixed  $options
     *
     * @return list<string>
     */
    public function getClassesFromListRecursively($namespace, $options)
    {
        if (! str_contains($this->namespace, 'Views')) {
            $initialClasses = str_contains($this->namespace, $namespace) ? $this->getDirectClassesOnly($options) : [];
            $result         = array_reduce($this->getDirectSubnamespaces(), static fn ($carry, PSR4Namespace $subNamespace) => array_merge($carry, $subNamespace->getClassesFromListRecursively($namespace, $options)), $initialClasses);
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * Join an absolute path and a relative path in a platform agnostic way.
     *
     * This method is also extracted so that it can be turned into a vfs:// stream URL for unit testing.
     *
     * @param string $directory
     * @param string $relativePath
     *
     * @return mixed
     */
    public function normalizePath($directory, $relativePath)
    {
        return str_replace('\\', '/', $directory . '/' . $relativePath);
    }

    /**
     * @return list<PSR4Namespace>
     */
    public function getDirectSubnamespaces()
    {
        return $this->directSubnamespaces;
    }

    /**
     * @param list<PSR4Namespace> $directSubnamespaces
     */
    public function setDirectSubnamespaces($directSubnamespaces)
    {
        $this->directSubnamespaces = $directSubnamespaces;
    }

    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return trim($this->namespace, '\\');
    }
}
