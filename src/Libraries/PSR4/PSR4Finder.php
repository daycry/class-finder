<?php

namespace Daycry\ClassFinder\Libraries\PSR4;

use Daycry\ClassFinder\ClassFinder;
use Daycry\ClassFinder\Interfaces\FinderInterface;

class PSR4Finder implements FinderInterface
{
    private PSR4Factory $factory;

    public function __construct()
    {
        $this->factory = new PSR4Factory();
    }

    public function getPriority(): int
    {
        return 1; // High priority for PSR4
    }

    public function findClasses(string $namespace, $options): array
    {
        $applicableNamespaces = [];

        if ($options & ClassFinder::RECURSIVE_MODE) {
            $applicableNamespaces = $this->findAllApplicableNamespaces($namespace);
        }

        if (empty($applicableNamespaces)) {
            $bestNamespace = $this->findBestPSR4Namespace($namespace);
            if ($bestNamespace !== null) {
                $applicableNamespaces = [$bestNamespace];
            }
        }

        $classes = [];

        foreach ($applicableNamespaces as $psr4Namespace) {
            if ($psr4Namespace instanceof PSR4Namespace) {
                $classes = array_merge($classes, $psr4Namespace->findClasses($namespace, $options));
            }
        }

        return $classes;
    }

    public function isNamespaceKnown(string $namespace): bool
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        foreach ($composerNamespaces as $psr4Namespace) {
            if ($psr4Namespace->knowsNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<PSR4Namespace>
     */
    private function findAllApplicableNamespaces(string $namespace): array
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        return array_filter($composerNamespaces, static fn (PSR4Namespace $potentialNamespace) => $potentialNamespace->isAcceptableNamespaceRecursiveMode($namespace));
    }

    private function findBestPSR4Namespace(string $namespace): ?PSR4Namespace
    {
        $composerNamespaces = $this->factory->getPSR4Namespaces();

        $acceptableNamespaces = array_filter($composerNamespaces, static fn (PSR4Namespace $potentialNamespace) => $potentialNamespace->isAcceptableNamespace($namespace));

        if (empty($acceptableNamespaces)) {
            return null;
        }

        $bestNamespace           = null;
        $highestMatchingSegments = 0;

        foreach ($acceptableNamespaces as $potentialNamespace) {
            $matchingSegments = $potentialNamespace->countMatchingNamespaceSegments($namespace);

            if ($matchingSegments > $highestMatchingSegments) {
                $highestMatchingSegments = $matchingSegments;
                $bestNamespace           = $potentialNamespace;
            }
        }

        return $bestNamespace;
    }
}
