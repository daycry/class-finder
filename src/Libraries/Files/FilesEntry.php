<?php

namespace Daycry\ClassFinder\Libraries\Files;

use Daycry\ClassFinder\ClassFinder;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class FilesEntry
{
    private string $file;
    private string $php;
    private ?array $cachedClasses                = null;
    private static ?ParserFactory $parserFactory = null;

    public function __construct(string $fileToInclude, string $php)
    {
        $this->file = $this->normalizePath($fileToInclude);
        $this->php  = $php;
    }

    public function knowsNamespace(string $namespace): bool
    {
        $classes = $this->getClassesInFile(ClassFinder::ALLOW_ALL);

        foreach ($classes as $class) {
            if (str_contains($class, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a list of classes that belong to the given namespace.
     *
     * @return list<string>
     */
    public function getClasses(string $namespace, int $options): array
    {
        $classes = $this->getClassesInFile($options);

        return array_values(array_filter($classes, static function ($class) use ($namespace) {
            $classNameFragments = explode('\\', $class);

            if (count($classNameFragments) > 1) {
                array_pop($classNameFragments);
            }

            $classNamespace = implode('\\', $classNameFragments);
            $namespace      = trim($namespace, '\\');

            return $namespace === $classNamespace;
        }));
    }

    /**
     * Dynamically execute files and check for defined classes.
     *
     * Uses static analysis with PHP-Parser for better performance and security.
     */
    private function getClassesInFile(int $options): array
    {
        if ($this->cachedClasses !== null) {
            return $this->filterClassesByOptions($this->cachedClasses, $options);
        }

        try {
            // Try static analysis first (faster and safer)
            $this->cachedClasses = $this->parseFileStatically();
        } catch (Error $e) {
            // Fallback to dynamic analysis if static analysis fails
            $this->cachedClasses = $this->parseFileDynamically();
        }

        return $this->filterClassesByOptions($this->cachedClasses, $options);
    }

    /**
     * Parse PHP file using static analysis (PHP-Parser)
     */
    private function parseFileStatically(): array
    {
        if (! file_exists($this->file)) {
            return ['classes' => [], 'interfaces' => [], 'traits' => [], 'functions' => []];
        }

        $code = file_get_contents($this->file);
        if ($code === false) {
            return ['classes' => [], 'interfaces' => [], 'traits' => [], 'functions' => []];
        }

        if (self::$parserFactory === null) {
            self::$parserFactory = new ParserFactory();
        }

        // Fixed API call for php-parser v5.x
        $parser = self::$parserFactory->createForNewestSupportedVersion();
        $ast    = $parser->parse($code);

        if ($ast === null) {
            return ['classes' => [], 'interfaces' => [], 'traits' => [], 'functions' => []];
        }

        $visitor   = new ClassExtractionVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getAllElements();
    }

    /**
     * Fallback: Parse file using dynamic analysis (original exec method)
     */
    private function parseFileDynamically(): array
    {
        $initialData = $this->execReturn("var_export(array(get_declared_interfaces(), get_declared_classes(), get_declared_traits(), get_defined_functions()['user']));");

        $allData = $this->execReturn("require_once '{$this->file}'; var_export(array(get_declared_interfaces(), get_declared_classes(), get_declared_traits(), get_defined_functions()['user']));");

        return [
            'interfaces' => array_diff($allData[0], $initialData[0]),
            'classes'    => array_diff($allData[1], $initialData[1]),
            'traits'     => array_diff($allData[2], $initialData[2]),
            'functions'  => array_diff($allData[3], $initialData[3]),
        ];
    }

    /**
     * Filter cached classes by options
     */
    private function filterClassesByOptions(array $cachedClasses, int $options): array
    {
        $final = [];

        if ($options & ClassFinder::ALLOW_CLASSES) {
            $final = array_merge($final, $cachedClasses['classes']);
        }
        if ($options & ClassFinder::ALLOW_INTERFACES) {
            $final = array_merge($final, $cachedClasses['interfaces']);
        }
        if ($options & ClassFinder::ALLOW_TRAITS) {
            $final = array_merge($final, $cachedClasses['traits']);
        }
        if ($options & ClassFinder::ALLOW_FUNCTIONS) {
            $final = array_merge($final, $cachedClasses['functions']);
        }

        return $final;
    }

    /**
     * Execute PHP code and return returned value
     *
     * @return mixed
     */
    private function execReturn(string $script): array
    {
        $command = $this->php . " -r \"{$script}\"";
        exec($command, $output, $return);

        if ($return === 0 && ! empty($output)) {
            $classes = 'return ' . implode('', $output) . ';';
            $result  = eval($classes);

            return is_array($result) ? $result : [[], [], [], []];
        }

        return [[], [], [], []];
    }

    /**
     * Normalize file path separators
     */
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
