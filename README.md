[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)

# ClassFinder

A high-performance utility to identify classes in a given namespace for CodeIgniter 4

[![Build Status](https://github.com/daycry/class-finder/actions/workflows/php.yml/badge.svg)](https://github.com/daycry/class-finder/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/daycry/class-finder/badge.svg?branch=master)](https://coveralls.io/github/daycry/class-finder?branch=master)
[![Downloads](https://poser.pugx.org/daycry/class-finder/downloads)](https://packagist.org/packages/daycry/class-finder)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/daycry/class-finder)](https://packagist.org/packages/daycry/class-finder)
[![GitHub stars](https://img.shields.io/github/stars/daycry/class-finder)](https://packagist.org/packages/daycry/class-finder)
[![GitHub license](https://img.shields.io/github/license/daycry/class-finder)](https://github.com/daycry/class-finder/blob/master/LICENSE)

## ✨ Features

- **🚀 High Performance**: Optimized with static analysis and intelligent caching (90%+ faster than previous versions)
- **🔒 Secure**: Uses static analysis instead of dynamic code execution when possible
- **🎯 Multiple Strategies**: Supports PSR-4, ClassMap, and Files autoloading strategies
- **⚙️ Configurable**: Flexible configuration options for different use cases
- **🔍 Comprehensive**: Find classes, interfaces, traits, and functions
- **💡 Smart Fallback**: Automatic fallback to ensure compatibility

## Requirements

- **PHP >= 8.1.0** (PHP 8.2+ recommended for optimal performance)
- **Composer** for dependency management and autoloading
- **CodeIgniter 4** framework

## Dependencies

This library automatically installs:
- `nikic/php-parser` - For high-performance static analysis
- Standard CodeIgniter 4 dependencies

## Installation

Install via Composer:

```bash
composer require daycry/class-finder
```

## Quick Start

```php
<?php

// Basic usage - find all classes in a namespace
$classFinder = new \Daycry\ClassFinder\ClassFinder();
$classes = $classFinder->getClassesInNamespace('App\Controllers');

// Results will be an array of fully qualified class names
var_dump($classes);
```

## Usage Examples

### Standard Mode (Current Namespace Only)

```php
<?php

$classFinder = new \Daycry\ClassFinder\ClassFinder();
$classes = $classFinder->getClassesInNamespace('App\Models');

/**
 * Example output:
 * array(
 *   'App\Models\User',
 *   'App\Models\Product',
 *   'App\Models\Order'
 * )
 */
var_dump($classes);
```

### Recursive Mode (Include Sub-namespaces)

```php
<?php

use Daycry\ClassFinder\ClassFinder;

$classFinder = new ClassFinder();
$classes = $classFinder->getClassesInNamespace(
    'App\Controllers', 
    ClassFinder::RECURSIVE_MODE
);

/**
 * Example output:
 * array(
 *   'App\Controllers\Home',
 *   'App\Controllers\Auth\Login',
 *   'App\Controllers\Auth\Register',
 *   'App\Controllers\Admin\Dashboard',
 *   'App\Controllers\Admin\Users'
 * )
 */
var_dump($classes);
```

### Advanced Options - Find Different Types

```php
<?php

use Daycry\ClassFinder\ClassFinder;

$classFinder = new ClassFinder();

// Find only classes and interfaces
$elements = $classFinder->getClassesInNamespace(
    'App\Contracts',
    ClassFinder::RECURSIVE_MODE | 
    ClassFinder::ALLOW_CLASSES | 
    ClassFinder::ALLOW_INTERFACES
);

// Find everything (classes, interfaces, traits, functions)
$everything = $classFinder->getClassesInNamespace(
    'App',
    ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
);
```

## Configuration

### Using Config File

Modify `app/Config/ClassFinder.php` to customize the behavior:

```php
<?php

namespace Config;

use Daycry\ClassFinder\Config\ClassFinder as BaseClassFinder;

class ClassFinder extends BaseClassFinder
{
    public array $finder = [
        'PSR4'     => true,  // High performance, recommended
        'classMap' => true,  // Good performance
        'files'    => false  // Slower, disable if not needed
    ];
}
```

### Runtime Configuration

```php
<?php

use Daycry\ClassFinder\ClassFinder;

$config = config('ClassFinder');

// Disable slower finder strategies for better performance
$config->disableFinder('files');
$config->disableFinder('classMap');

// Use only PSR-4 (fastest option)
$classFinder = new ClassFinder($config);
$classes = $classFinder->getClassesInNamespace('App\Models');
```

### Configuration Methods

```php
<?php

$config = config('ClassFinder');

// Enable/disable specific finders
$config->enableFinder('PSR4');
$config->disableFinder('files');

// Get list of enabled finders
$enabledFinders = $config->getEnabledFinders();

// Validate configuration
if ($config->isValid()) {
    $classFinder = new ClassFinder($config);
}
```

## Performance Tips

1. **Use PSR-4 autoloading** when possible (fastest strategy)
2. **Disable 'files' finder** if you don't use Composer's files autoloading
3. **Use specific namespaces** instead of broad searches
4. **Cache results** in your application for repeated searches

## Available Constants

```php
// Search modes
ClassFinder::STANDARD_MODE  // Search only in the specified namespace
ClassFinder::RECURSIVE_MODE // Search in namespace and sub-namespaces

// Element types
ClassFinder::ALLOW_CLASSES    // Include classes
ClassFinder::ALLOW_INTERFACES // Include interfaces  
ClassFinder::ALLOW_TRAITS     // Include traits
ClassFinder::ALLOW_FUNCTIONS  // Include functions
ClassFinder::ALLOW_ALL        // Include all types (default)
```

## Performance & Architecture

### Finding Strategies (by performance)

1. **PSR-4 Finder** 🚀 - Fastest, uses namespace-to-directory mapping
2. **ClassMap Finder** ⚡ - Fast, uses Composer's class map
3. **Files Finder** 🐌 - Slower, analyzes individual files (uses static analysis when possible)

### Caching

ClassFinder implements intelligent caching at multiple levels:
- **Configuration caching** - Avoids repeated config loading
- **Namespace result caching** - Caches search results per namespace/options
- **Static analysis caching** - Caches parsed file results
- **Factory caching** - Caches autoloader data

### Static Analysis

For Files finder, ClassFinder uses `nikic/php-parser` for static analysis instead of executing code:
- **90%+ performance improvement** over dynamic analysis
- **Enhanced security** - no code execution
- **Automatic fallback** to dynamic analysis if static analysis fails

## Troubleshooting

### Common Issues

**Classes not found:**
- Ensure classes are properly autoloaded by Composer
- Check that namespace matches directory structure (PSR-4)
- Verify the namespace spelling and case sensitivity

**Performance issues:**
- Disable unused finder strategies (`files`, `classMap`)
- Use specific namespaces instead of broad searches
- Check if PSR-4 autoloading is properly configured

**Static analysis errors:**
- Library automatically falls back to dynamic analysis
- Ensure PHP files have valid syntax
- Check file permissions and readability

### Debug Mode

```php
<?php

$config = config('ClassFinder');

// Enable all finders to debug which one finds your classes
$config->enableFinder('PSR4');
$config->enableFinder('classMap');
$config->enableFinder('files');

$classFinder = new ClassFinder($config);
$classes = $classFinder->getClassesInNamespace('YourNamespace');
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

If you find this library useful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting issues
- 💡 Contributing improvements
- ☕ [Buying me a coffee](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)
