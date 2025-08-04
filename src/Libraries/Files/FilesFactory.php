<?php

namespace Daycry\ClassFinder\Libraries\Files;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Daycry\ClassFinder\Libraries\BaseFactory;

class FilesFactory extends BaseFactory
{
    private ?array $filesEntriesCache = null;
    private ?string $phpPathCache     = null;

    /**
     * @return list<FilesEntry>
     */
    public function getFilesEntries(): array
    {
        if ($this->filesEntriesCache !== null) {
            return $this->filesEntriesCache;
        }

        $files = array_merge(
            require (ROOTPATH . 'vendor/composer/autoload_files.php'),
            $this->loadAutoloadConfigFiles(),
        );

        if (empty($files)) {
            return $this->filesEntriesCache = [];
        }

        // PHP path is only needed for fallback dynamic analysis
        $phpPath      = $this->findPHP();
        $filesEntries = [];

        foreach (array_values($files) as $file) {
            $filesEntries[] = new FilesEntry($file, $phpPath);
        }

        return $this->filesEntriesCache = $filesEntries;
    }

    /**
     * Locates the PHP interpreter.
     *
     * If PHP 5.4 or newer is used, the PHP_BINARY value is used.
     * Otherwise we attempt to find it from shell commands.
     *
     * @throws ClassFinderException
     *
     * @codeCoverageIgnore
     */
    private function findPHP(): string
    {
        if ($this->phpPathCache !== null) {
            return $this->phpPathCache;
        }

        if (defined('PHP_BINARY') && ! empty(PHP_BINARY)) {
            return $this->phpPathCache = PHP_BINARY;
        }

        $isHostWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $command       = $isHostWindows ? 'where php' : 'which php';

        exec($command, $output, $return);

        if ($return === 0 && ! empty($output[0])) {
            return $this->phpPathCache = $output[0];
        }

        throw new ClassFinderException(sprintf(
            'Could not locate PHP interpreter. See "%s" for details.',
            'https://gitlab.com/hpierce1102/ClassFinder/blob/master/docs/exceptions/filesCouldNotLocatePHP.md',
        ));
    }
}
