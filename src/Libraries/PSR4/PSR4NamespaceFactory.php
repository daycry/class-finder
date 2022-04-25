<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Config\Services;
class PSR4NamespaceFactory
{
    /**
     * @return string[]
     */
    public function getPSR4Namespaces()
    {

        $loader  = Services::autoloader();

        /*$namespaces = $this->getUserDefinedPSR4Namespaces();
        $vendorNamespaces = require(ROOTPATH . 'vendor/composer/autoload_psr4.php');*/

        $namespaces = $loader->getNamespace();

        $names = array_keys($namespaces);
        $directories = array_values($namespaces);
        $self = $this;
        $namespaces = array_map(function($index) use ($self, $names, $directories) {
            return $self->createNamespace($names[$index], $directories[$index]);
        },range(0, count($namespaces) - 1));

        return $namespaces;
    }

    /**
     * @return string[]
     */
    /*private function getUserDefinedPSR4Namespaces()
    {
        $composerJsonPath = ROOTPATH . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));

        if (!isset($composerConfig->autoload)) {
            return array();
        }

        //Apparently PHP doesn't like hyphens, so we use variable variables instead.
        $psr4 = "psr-4";
        return (array)$composerConfig->autoload->$psr4;
    }*/

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
            // This is an acceptable format according to composer.json
            $directories = array($directories);
        } elseif (is_array($directories)) {
            // composer_psr4.php seems to put everything in this format
        } else {
            throw new ClassFinderException('Unknown PSR4 definition.');
        }

        $self = $this;
        $directories = array_map(function($directory) use ($self) {
            if ($self->isAbsolutePath($directory)) {
                return $directory;
            } else {
                return ROOTPATH . $directory;
            }
        }, $directories);

        $directories = array_filter(array_map(function($directory) {
            return realpath($directory);
        }, $directories));

        $psr4Namespace = new PSR4Namespace($namespace, $directories);

        $subNamespaces = $this->getSubnamespaces($psr4Namespace);
        $psr4Namespace->setDirectSubnamespaces($subNamespaces);

        return $psr4Namespace;
    }

    /**
     * @param PSR4Namespace $psr4Namespace
     * @return PSR4Namespace[]
     */
    private function getSubnamespaces(PSR4Namespace $psr4Namespace)
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
        if (!is_string($path)) {
            $mess = sprintf('String expected but was given %s', gettype($path));
            throw new ClassFinderException($mess);
        }

        // Optional wrapper(s).
        $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
        // Optional root prefix.
        $regExp .= '(?<root>(?:[[:alpha:]]:[/\\\\]|/)?)';
        // Actual path.
        $regExp .= '(?<path>(?:[[:print:]]*))$%';
        $parts = array();
        if (!preg_match($regExp, $path, $parts)) {
            $mess = sprintf('Path is NOT valid, was given %s', $path);
            throw new ClassFinderException($mess);
        }
        if ('' !== $parts['root']) {
            return true;
        }
        return false;
    }
}
