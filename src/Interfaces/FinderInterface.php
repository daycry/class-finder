<?php

namespace Daycry\ClassFinder\Interfaces;

interface FinderInterface
{
    /**
     * Find classes in a given namespace.
     *
     * @return list<string>
     */
    public function findClasses(string $namespace, int $options): array;

    /**
     * Check if a given namespace is known.
     *
     * A namespace is "known" if a Finder can determine that the autoloader can create classes from that namespace.
     *
     * For instance:
     * If given a classmap for "TestApp1\Foo\Bar\Baz", the namespace "TestApp1\Foo" is known, even if nothing loads
     * from that namespace directly. It is known because classes that include that namespace are known.
     */
    public function isNamespaceKnown(string $namespace): bool;

    /**
     * Get the priority of this finder (optional, for ordering)
     * Lower numbers = higher priority
     */
    public function getPriority(): int;
}
