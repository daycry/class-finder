<?php

namespace Daycry\ClassFinder\Libraries\Files;

use Daycry\ClassFinder\Exceptions\ClassFinderException;
use Daycry\ClassFinder\Libraries\BaseFactory;

class FilesFactory extends BaseFactory
{
    /**
     * @return FilesEntry[]
     */
    public function getFilesEntries()
    {
        $files = require(ROOTPATH . 'vendor/composer/autoload_files.php');
        $files = \array_merge($files, $this->loadAutoloadConfigFiles());

        $phpPath = $this->findPHP();

        $filesKeys = array_values($files);
        return array_map(function ($index) use ($filesKeys, $phpPath) {
            return new FilesEntry($filesKeys[$index], $phpPath);
        }, range(0, count($files) - 1));
    }

    /**
     * Locates the PHP interrupter.
     *
     * If PHP 5.4 or newer is used, the PHP_BINARY value is used.
     * Otherwise we attempt to find it from shell commands.
     *
     * @return string
     * @throws ClassFinderException
     *
     * @codeCoverageIgnore
     */
    private function findPHP()
    {
        if (defined("PHP_BINARY")) {
            // PHP_BINARY was made available in PHP 5.4
            $php = PHP_BINARY;
        } else {
            $isHostWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            if ($isHostWindows) {
                exec('where php', $output);
                $php = $output[0];
            } else {
                exec('which php', $output);
                $php = $output[0];
            }
        }

        if (!isset($php)) {
            throw new ClassFinderException(sprintf(
                'Could not locate PHP interrupter. See "%s" for details.',
                'https://gitlab.com/hpierce1102/ClassFinder/blob/master/docs/exceptions/filesCouldNotLocatePHP.md'
            ));
        }

        return $php;
    }
}
