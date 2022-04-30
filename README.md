[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)

# ClassFinder
===========

A dead simple utility to identify classes in a given namespace for Codeigniter 4

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

```
<?php

$classes = (new \Daycry\ClassFinder\ClassFinder)->getClassesInNamespace('Daycry');

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

```
<?php

$classes = (new \Daycry\ClassFinder\ClassFinder)->getClassesInNamespace('Daycry', \Daycry\ClassFinder\ClassFinder::RECURSIVE_MODE);

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