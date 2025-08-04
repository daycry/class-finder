<?php

namespace Daycry\ClassFinder\Libraries\Files;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Daycry\ClassFinder\Interfaces\FinderInterface;

class FilesFinder implements FinderInterface
{
    private FilesFactory $factory;

    /**
     * @throws ClassFinderException
     */
    public function __construct()
    {
        $this->factory = new FilesFactory();

        if (! function_exists('exec')) {
            throw new ClassFinderException(sprintf(
                'FilesFinder requires that exec() is available. Check your php.ini to see if it is disabled. See "%s" for details.',
                'https://gitlab.com/hpierce1102/ClassFinder/blob/master/docs/exceptions/filesExecNotAvailable.md',
            ));
        }
    }

    public function getPriority(): int
    {
        return 3; // Low priority for Files
    }

    public function isNamespaceKnown(string $namespace): bool
    {
        $filesEntries = $this->factory->getFilesEntries();

        foreach ($filesEntries as $filesEntry) {
            if ($filesEntry->knowsNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function findClasses(string $namespace, int $options): array
    {
        $filesEntries = $this->factory->getFilesEntries();

        $classes = [];

        foreach ($filesEntries as $entry) {
            $classes = array_merge($classes, $entry->getClasses($namespace, $options));
        }

        return $classes;
    }
}
