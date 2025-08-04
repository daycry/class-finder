<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Daycry\ClassFinder\Libraries\BaseFactory;

class PSR4Factory extends BaseFactory
{
    private ?array $psr4NamespacesCache = null;

    /**
     * @return list<PSR4Namespace>
     */
    public function getPSR4Namespaces(): array
    {
        if ($this->psr4NamespacesCache !== null) {
            return $this->psr4NamespacesCache;
        }

        $namespaces = array_merge($this->getPSR4(), $this->loadAutoloadConfigPsr4());

        $psr4Namespaces = [];

        foreach ($namespaces as $namespace => $directories) {
            $psr4Namespaces[] = $this->createNamespace($namespace, $directories);
        }

        return $this->psr4NamespacesCache = $psr4Namespaces;
    }

    /**
     * Creates a namespace from composer_psr4.php and composer.json autoload.psr4 items.
     *
     * @param string       $namespace
     * @param list<string> $directories
     *
     * @return PSR4Namespace
     *
     * @throws ClassFinderException
     */
    public function createNamespace($namespace, $directories)
    {
        if (is_string($directories)) {
            $directories = [$directories];
        } elseif (is_array($directories)) {
            // @codeCoverageIgnoreStart
        } else {
            throw new ClassFinderException('Unknown PSR4 definition.');
        }
        // @codeCoverageIgnoreEnd

        $self        = $this;
        $directories = array_map(static function ($directory) use ($self) {
            if ($self->isAbsolutePath($directory)) {
                return $directory;
                // @codeCoverageIgnoreStart
            }

            return ROOTPATH . $directory;
            // @codeCoverageIgnoreEnd
        }, $directories);

        $directories = array_filter(array_map(static fn ($directory) => realpath($directory), $directories));

        $psr4Namespace = new PSR4Namespace($namespace, $directories);

        $subNamespaces = $this->getSubNamespaces($psr4Namespace);

        $psr4Namespace->setDirectSubnamespaces($subNamespaces);

        return $psr4Namespace;
    }

    /**
     * @return list<PSR4Namespace>
     */
    private function getSubNamespaces(PSR4Namespace $psr4Namespace)
    {
        // Scan it's own directories.
        $directories = $psr4Namespace->findDirectories();

        $self = $this;

        return array_map(static function ($directory) use ($self, $psr4Namespace) {
            $segments = explode('/', $directory);

            $subnamespaceSegment = array_pop($segments);

            $namespace = $psr4Namespace->getNamespace() . '\\' . $subnamespaceSegment . '\\';

            return $self->createNamespace($namespace, $directory);
        }, $directories);
    }

    /**
     * Check if a path is absolute.
     *
     * Mostly this answer https://stackoverflow.com/a/38022806/3000068
     * A few changes: Changed exceptions to be ClassFinderExceptions, removed some ctype dependencies,
     * updated the root prefix regex to handle Window paths better.
     *
     * @param string $path
     *
     * @return bool
     *
     * @throws ClassFinderException
     */
    public function isAbsolutePath($path)
    {
        // @codeCoverageIgnoreStart
        if (! is_string($path)) {
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
        $parts = [];
        if (! preg_match($regExp, $path, $parts)) {
            // @codeCoverageIgnoreStart
            $mess = sprintf('Path is NOT valid, was given %s', $path);

            throw new ClassFinderException($mess);
            // @codeCoverageIgnoreEnd
        }

        return (bool) ('' !== $parts['root']);
        // @codeCoverageIgnoreStart
        // @codeCoverageIgnoreEnd
    }
}
