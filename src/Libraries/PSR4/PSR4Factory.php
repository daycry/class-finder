<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\Exceptions\ClassFinderException;

class PSR4Factory extends \Daycry\ClassFinder\Libraries\BaseFactory
{
    /**
     * @return string[]
     */
    public function getPSR4Namespaces()
    {
        $namespaces = $this->getPSR4();

        $names = array_keys($namespaces);
        $directories = array_values($namespaces);
        $self = $this;
        $namespaces = array_map(function($index) use ($self, $names, $directories) {
            return $self->createNamespace($names[$index], $directories[$index]);
        },range(0, count($namespaces) - 1));

        return $namespaces;
    }

    /**
     * Creates a namespace from composer_psr4.php and composer.json autoload.psr4 items.
     *
     * @param string $namespace
     * @param string[] $directories
     * @return PSR4Namespace
     * @throws ClassFinderException
     */
    public function createNamespace($namespace, $directories)
    {
        if (is_string($directories)) {
            $directories = array($directories);
        } elseif (is_array($directories)) {
        // @codeCoverageIgnoreStart
        } else {
            throw new ClassFinderException('Unknown PSR4 definition.');
        }
        // @codeCoverageIgnoreEnd

        $self = $this;
        $directories = array_map(function($directory) use ($self) {
            if ($self->isAbsolutePath($directory)) {
                return $directory;
            // @codeCoverageIgnoreStart
            } else {
                return ROOTPATH . $directory;
            }
            // @codeCoverageIgnoreEnd
        }, $directories);

        $directories = array_filter(array_map(function($directory) {
            return realpath($directory);
        }, $directories));

        $psr4Namespace = new PSR4Namespace($namespace, $directories);

        $subNamespaces = $this->getSubNamespaces($psr4Namespace);

        $psr4Namespace->setDirectSubnamespaces($subNamespaces);

        return $psr4Namespace;
    }

    /**
     * @param PSR4Namespace $psr4Namespace
     * @return PSR4Namespace[]
     */
    private function getSubNamespaces(PSR4Namespace $psr4Namespace)
    {
        // Scan it's own directories.
        $directories = $psr4Namespace->findDirectories();

        $self = $this;
        $subnamespaces = array_map(function($directory) use ($self, $psr4Namespace){
            $segments = explode('/', $directory);

            $subnamespaceSegment = array_pop($segments);

            $namespace = $psr4Namespace->getNamespace() . "\\" . $subnamespaceSegment . "\\";

            return $self->createNamespace($namespace, $directory);
        }, $directories);

        return $subnamespaces;
    }

    /**
     * Check if a path is absolute.
     *
     * Mostly this answer https://stackoverflow.com/a/38022806/3000068
     * A few changes: Changed exceptions to be ClassFinderExceptions, removed some ctype dependencies,
     * updated the root prefix regex to handle Window paths better.
     *
     * @param string $path
     * @return bool
     * @throws ClassFinderException
     */
    public function isAbsolutePath($path) {
        // @codeCoverageIgnoreStart
        if (!is_string($path)) {
            $mess = sprintf('String expected but was given %s', gettype($path));
            throw new ClassFinderException($mess);
        }
        // @codeCoverageIgnoreEnd

        // Optional wrapper(s).
        $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
        // Optional root prefix.
        $regExp .= '(?<root>(?:[[:alpha:]]:[/\\\\]|/)?)';
        // Actual path.
        $regExp .= '(?<path>(?:[[:print:]]*))$%';
        $parts = array();
        if (!preg_match($regExp, $path, $parts)) {
            // @codeCoverageIgnoreStart
            $mess = sprintf('Path is NOT valid, was given %s', $path);
            throw new ClassFinderException($mess);
            // @codeCoverageIgnoreEnd
        }
        if ('' !== $parts['root']) {
            return true;
        }
        // @codeCoverageIgnoreStart
        return false;
        // @codeCoverageIgnoreEnd
    }
}