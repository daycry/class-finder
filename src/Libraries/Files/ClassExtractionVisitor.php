<?php

namespace Daycry\ClassFinder\Libraries\Files;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ClassExtractionVisitor extends NodeVisitorAbstract
{
    private array $classes           = [];
    private array $interfaces        = [];
    private array $traits            = [];
    private array $functions         = [];
    private string $currentNamespace = '';

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name ? $node->name->toString() : '';
        } elseif ($node instanceof Node\Stmt\Class_) {
            $this->classes[] = $this->getFullyQualifiedName($node->name->name);
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $this->interfaces[] = $this->getFullyQualifiedName($node->name->name);
        } elseif ($node instanceof Node\Stmt\Trait_) {
            $this->traits[] = $this->getFullyQualifiedName($node->name->name);
        } elseif ($node instanceof Node\Stmt\Function_) {
            $this->functions[] = $this->getFullyQualifiedName($node->name->name);
        }
    }

    private function getFullyQualifiedName(string $name): string
    {
        if (empty($this->currentNamespace)) {
            return $name;
        }

        return $this->currentNamespace . '\\' . $name;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function getTraits(): array
    {
        return $this->traits;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getAllElements(): array
    {
        return [
            'classes'    => $this->classes,
            'interfaces' => $this->interfaces,
            'traits'     => $this->traits,
            'functions'  => $this->functions,
        ];
    }

    public function reset(): void
    {
        $this->classes          = [];
        $this->interfaces       = [];
        $this->traits           = [];
        $this->functions        = [];
        $this->currentNamespace = '';
    }
}
