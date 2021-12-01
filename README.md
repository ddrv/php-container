[![Latest Stable Version](https://img.shields.io/packagist/v/ddrv/container.svg?style=flat-square)](https://packagist.org/packages/ddrv/container)
[![Total Downloads](https://img.shields.io/packagist/dt/ddrv/container.svg?style=flat-square)](https://packagist.org/packages/ddrv/container/stats)
[![License](https://img.shields.io/packagist/l/ddrv/container.svg?style=flat-square)](https://github.com/ddrv/php-container/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/ddrv/container.svg?style=flat-square)](https://php.net)

# ddrv/container

Simple PSR-11 Container 

# Install

```bash
composer require ddrv/container:^2.0
```

# Using

```php
<?php

use Ddrv\Container\Container;
use Psr\Container\ContainerInterface;

$container = new Container();

/*
 * Set value as is
 */
$container->value('var', 'value1');
$container->get('var'); // returns 'value1'

/*
 * Set factory for service
 */
$container->service('service', function(ContainerInterface $container) {
    $service = new stdClass();
    $service->var = $container->get('var');
    return $service;
});
$service1 = $container->get('service');
$service2 = $container->get('service');
$equal = $service1 === $service2; // true
$service1->var; // 'value1'
$service2->var; // 'value1'
$service1->var = 'value2';
$service1->var; // 'value2'
$service2->var; // 'value2'

/*
 * Set factory for recreated instance
 */
$container->instance('instance', function(ContainerInterface $container) {
    $instance = new stdClass();
    $instance->var = $container->get('var');
    return $instance;
});
$instance1 = $container->get('instance');
$instance2 = $container->get('instance');
$equal = $service1 === $service2; // false
$instance1->var; // 'value1'
$instance2->var; // 'value1'
$instance1->var = 'value2';
$instance1->var; // 'value2'
$instance2->var; // 'value1'

/*
 * Aliasing 
 */
$container->service('some-service', function() {
    return new ArrayObject();
});
$container->bind('alias', 'some-service');
$service = $container->get('alias');

/*
 * Delegating to other containers
 */

/** @var ContainerInterface $delegate1 */
/** @var ContainerInterface $delegate2 */
$delegate1->has('key-1'); // true
$delegate1->has('key-2'); // false

$delegate2->has('key-1'); // false
$delegate2->has('key-2'); // true

$container->has('key-1'); // false
$container->has('key-2'); // false

$container->delegate($delegate1);
$container->has('key-1'); // true
$container->has('key-2'); // false

$container->delegate($delegate2);
$container->has('key-1'); // true
$container->has('key-2'); // true
```
