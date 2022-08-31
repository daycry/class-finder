[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)

# ClassFinder
===========

A dead simple utility to identify classes in a given namespace for Codeigniter 4

[![Build Status](https://github.com/daycry/class-finder/workflows/PHP%20Tests/badge.svg)](https://github.com/daycry/class-finder/actions?query=workflow%3A%22PHP+Tests%22)
[![Coverage Status](https://coveralls.io/repos/github/daycry/class-finder/badge.svg?branch=master)](https://coveralls.io/github/daycry/class-finder?branch=master)
[![Downloads](https://poser.pugx.org/daycry/class-finder/downloads)](https://packagist.org/packages/daycry/class-finder)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/daycry/class-finder)](https://packagist.org/packages/daycry/class-finder)
[![GitHub stars](https://img.shields.io/github/stars/daycry/class-finder)](https://packagist.org/packages/daycry/class-finder)
[![GitHub license](https://img.shields.io/github/license/daycry/class-finder)](https://github.com/daycry/class-finder/blob/master/LICENSE)

Requirements
------------

* Application is using Composer.
* Classes can be autoloaded with Composer.
* PHP >= 7.4.0

Installing
----------

Installing is done by requiring it with Composer.

```
$ composer require daycry/class-finder
```

Examples
--------

**Standard Mode**

```php
<?php

$classes = (new \Daycry\ClassFinder\ClassFinder())->getClassesInNamespace('Daycry');

/**
 * array(
 *   'TestApp1\Foo\Bar',
 *   'TestApp1\Foo\Baz',
 *   'TestApp1\Foo\Foo'
 * )
 */
var_dump($classes);
```

**Recursive Mode**

```php
<?php

$classes = (new \Daycry\ClassFinder\ClassFinder())->getClassesInNamespace('Daycry', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE);

/**
 * array(
 *   'TestApp1\Foo\Bar',
 *   'TestApp1\Foo\Baz',
 *   'TestApp1\Foo\Foo',
 *   'TestApp1\Foo\Box\Bar',
 *   'TestApp1\Foo\Box\Baz',
 *   'TestApp1\Foo\Box\Foo',
 *   'TestApp1\Foo\Box\Lon\Bar',
 *   'TestApp1\Foo\Box\Lon\Baz',
 *   'TestApp1\Foo\Box\Lon\Foo',
 * )
 */
var_dump($classes);
```


If you want to modify the configuration, you can modify the file Config/ClassFinder.php

or

Edit the configuration and pass it to the constructor

```php
<?php
$config = config('ClassFinder');

$config->finder['classMap'] = false;
$config->finder['files'] = false;

$classes = (new \Daycry\ClassFinder\ClassFinder($config))->getClassesInNamespace('Daycry', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE);
```

You can customize the search engine indicating if you want to search for classes, interfaces, traits or functions.

This library also integrates the **Autoload.php** class from the **Config** folder to perform searches.

```php
<?php
$config = config('ClassFinder');

$config->finder['classMap'] = false;
$config->finder['files'] = false;

$classes = (new \Daycry\ClassFinder\ClassFinder($config))->getClassesInNamespace('App', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE);

$classes = (new \Daycry\ClassFinder\ClassFinder($config))->getClassesInNamespace('Config', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE);
```

```php
<?php
$config = config('ClassFinder');

$config->finder['classMap'] = false;
$config->finder['files'] = false;

$classes = (new \Daycry\ClassFinder\ClassFinder($config))->getClassesInNamespace('Daycry', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE | \Daycry\ClassFinder\ClassFinder::ALLOW_CLASSES | \Daycry\ClassFinder\ClassFinder::ALLOW_INTERFACES | \Daycry\ClassFinder\ClassFinder::ALLOW_TRAITS | \Daycry\ClassFinder\ClassFinder::ALLOW_FUNCTIONS );
```