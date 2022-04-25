<?php

namespace Daycry\ClassFinder\Config;

use CodeIgniter\Config\BaseConfig;

class ClassFinder extends BaseConfig
{
    public array $finder = [
        'PSR4' => true
    ];

    public array $finderClass = [
        'PSR4' => \Daycry\ClassFinder\Libraries\PSR4\PSR4Finder::class
    ];
}
