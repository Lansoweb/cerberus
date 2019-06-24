# Cerberus

[![Build Status](https://travis-ci.org/lansoweb/cerberus.svg?branch=master)](https://travis-ci.org/lansoweb/cerberus) [![Coverage Status](https://coveralls.io/repos/Lansoweb/cerberus/badge.svg?branch=master&service=github)](https://coveralls.io/github/Lansoweb/cerberus?branch=master) [![Latest Stable Version](https://poser.pugx.org/lansoweb/cerberus/v/stable.svg)](https://packagist.org/packages/lansoweb/cerberus) [![Total Downloads](https://poser.pugx.org/lansoweb/cerberus/downloads.svg)](https://packagist.org/packages/lansoweb/cerberus) 

## Introduction

This is a Circuit Breaker pattern implementation in PHP.

This library helps you to handle external services timeouts and outages.

## Requirements

* PHP >= 7.2
* Any cache library implementing psr/simple-cache

## Installation

```
composer require los/cerberus:^1.0
```

## Configuration

You can manually create a Cerberus instance or use a Factory

### Factory

```php
'factories' => [
    Los\Cerberus\Cerberus::class => Los\Cerberus\CerberusFactory::class
],
```

and copy the configuration file config/cerberus.global.php.dist to your config/autoload/cerberus.global.php and change to your needs.

```php
return [
    'cerberus' => [
        'max_failures' => 5,
        'timeout' => 60,
    ]
];
```

The `maxFailure` parameter is the number of failures after which the circuit is opened and the service becomes not available.

When the `timeout` is reached, the circuit becomes half opened and one attempt is possible and the status is updated.

The factory pull the cache from the container using 
```php
$container->get(\Psr\SimpleCache\CacheInterface::class)
```

so you need to have one implemented.

### Manually

You can create a Cerberus instance manually:

```php
$storage = new Cache(); // Any psr/simple-cache implementation
$cerberus = new Cerberus($storage, 5, 60);
```

## Usage

The usage is simple. Each time you will access a remote resource (like an Web Service), check for its availability and report its success or failure:

```php
if ($cerberus->isAvailable()) {
    try {
        $http->makeRequest();
        $cerberus->reportSuccess();
    } catch (\Exception $ex) {
        $cerberus->reportFailure();
    }
}
``` 

You can use Cerberus to control more than one service. In this scenario, use the methods passing a service name:

```php
if ($cerberus->isAvailable('service-one')) {
    try {
        $http->makeRequest();
        $cerberus->reportSuccess('service-one');
    } catch (\Exception $ex) {
        $cerberus->reportFailure('service-one');
    }
}

if ($cerberus->isAvailable('service-two')) {
    try {
        $http->makeRequest();
        $cerberus->reportSuccess('service-two');
    } catch (\Exception $ex) {
        $cerberus->reportFailure('service-two');
    }
}
```
