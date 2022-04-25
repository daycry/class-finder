<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\Interfaces\FinderInterface;

class PSR4Finder implements FinderInterface
{
    private PSR4Factory $factory;

    public function __construct()
    {
        $this->factory = new PSR4Factory();
    }

    public function findClasses(string $namespace, int $options)
    {
        if ($options === \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE) {
            $applicableNamespaces = $this->_findAllApplicableNamespaces($namespace);
        }
        
        if (empty($applicableNamespaces)) {
            $bestNamespace = $this->_findBestPSR4Namespace($namespace);
            $applicableNamespaces = array($bestNamespace);
        }

        return array_reduce($applicableNamespaces, function($carry, $psr4NamespaceOrNull) use ($namespace, $options) {
            if ($psr4NamespaceOrNull instanceof PSR4Namespace) {
                $classes = $psr4NamespaceOrNull->findClasses($namespace, $options);
            } else {
                $classes = array();
            }

            return array_merge($carry, $classes);
        }, array());
    }

    public function isNamespaceKnown(string $namespace)
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        foreach($composerNamespaces as $psr4Namespace) {
            if ($psr4Namespace->knowsNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $namespace
     * @return PSR4Namespace[]
     */
    private function _findAllApplicableNamespaces($namespace)
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        return array_filter($composerNamespaces, function(PSR4Namespace $potentialNamespace) use ($namespace){
            return $potentialNamespace->isAcceptableNamespaceRecursiveMode($namespace);
        });
    }

    /**
     * @param string $namespace
     * @return PSR4Namespace
     */
    private function _findBestPSR4Namespace($namespace)
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        $acceptableNamespaces = array_filter($composerNamespaces, function(PSR4Namespace $potentialNamespace) use ($namespace){
            return $potentialNamespace->isAcceptableNamespace($namespace);
        });

        $carry = new \stdClass();
        $carry->highestMatchingSegments = 0;
        $carry->bestNamespace = null;

        /** @var PSR4Namespace $bestNamespace */
        $bestNamespace = array_reduce($acceptableNamespaces, function ($carry, PSR4Namespace $potentialNamespace) use ($namespace) {
            $matchingSegments = $potentialNamespace->countMatchingNamespaceSegments($namespace);

            if ($matchingSegments > $carry->highestMatchingSegments) {
                $carry->highestMatchingSegments = $matchingSegments;
                $carry->bestNamespace = $potentialNamespace;
            }

            return $carry;
        }, $carry);

        return $bestNamespace->bestNamespace;
    }
}
