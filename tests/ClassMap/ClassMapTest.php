<?php

namespace Tests\ClassMap;

use Daycry\ClassFinder\ClassFinder;
use Daycry\ClassFinder\Libraries\ClassMap\ClassMapEntry;
use CodeIgniter\Test\CIUnitTestCase;

class ClassMapTest extends CIUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testKnowsNamespace()
    {
        $entry = new ClassmapEntry("Daycry\Twig");

        $this->assertTrue($entry->knowsNamespace("Daycry"));
        $this->assertTrue($entry->knowsNamespace("Daycry\Twig"));

        $this->assertFalse($entry->knowsNamespace("Daycry\Example"));
        $this->assertFalse($entry->knowsNamespace("Daycry\Example\Bar"));
    }

    public function testMatches()
    {
        $entry = new ClassmapEntry("Daycry\Twig");

        $this->assertTrue($entry->matches("Daycry", ClassFinder::STANDARD_MODE));
        $this->assertFalse($entry->matches("Daycry\Twig\Bar", ClassFinder::STANDARD_MODE), "Providing the fully qualified classname doesn't match because only the class's namespace should match.");
        $this->assertFalse($entry->matches("Daycry\Example", ClassFinder::STANDARD_MODE));
        $this->assertFalse($entry->matches("MyClassmap\Twig\Baz", ClassFinder::STANDARD_MODE));
    }

    public function testStandardFind()
    {
        $config = config('ClassFinder');
        $config->finder['PSR4'] = false;
        $config->finder['files'] = false;

        $classes = (new ClassFinder($config))->getClassesInNamespace('SebastianBergmann\Diff');

        $this->assertContains('SebastianBergmann\Diff\Chunk', $classes);
        $this->assertNotContains('SebastianBergmann\Diff\Output\DiffOutputBuilderInterface', $classes);
    }

    public function testRecursiveInterfaceFind()
    {
        $config = config('ClassFinder');
        $config->finder['PSR4'] = false;
        $config->finder['files'] = false;

        $classes = (new ClassFinder($config))->getClassesInNamespace('SebastianBergmann\Diff', ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_INTERFACES);

        $this->assertContains('SebastianBergmann\Diff\Output\DiffOutputBuilderInterface', $classes);
        $this->assertNotContains('SebastianBergmann\Diff\Parser', $classes);
    }

    public function testRecursiveFind()
    {
        $config = config('ClassFinder');
        $config->finder['PSR4'] = false;
        $config->finder['files'] = false;

        $classes = (new ClassFinder($config))->getClassesInNamespace('SebastianBergmann\Diff', ClassFinder::RECURSIVE_MODE);

        $this->assertNotContains('SebastianBergmann\Diff\Output\DiffOutputBuilderInterface', $classes);
        $this->assertContains('SebastianBergmann\Diff\Parser', $classes);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
