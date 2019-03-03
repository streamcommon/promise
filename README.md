# PHP Promises/A implementation
[![Latest Stable Version](https://poser.pugx.org/streamcommon/promise/v/stable)](https://packagist.org/packages/streamcommon/promise)
[![Total Downloads](https://poser.pugx.org/streamcommon/promise/downloads)](https://packagist.org/packages/streamcommon/promise)
[![License](https://poser.pugx.org/streamcommon/promise/license)](./LICENSE)

This package provides [Promise/A](http://wiki.commonjs.org/wiki/Promises/A) PHP implementation.

# Branches
[![Master][Master branch image]][Master branch] [![Build Status][Master image]][Master] [![Coverage Status][Master coverage image]][Master coverage]

[![Develop][Develop branch image]][Develop branch] [![Build Status][Develop image]][Develop] [![Coverage Status][Develop coverage image]][Develop coverage]

## Installation
Console run:
```console
    composer require streamcommon/promise
```
Or add into your `composer.json`:
```json
    "require": {
        "streamcommon/promise": "*"
    }
```

## TRUE Promise
If you want see TRUE promise when install [Swoole](http://php.net/manual/en/swoole.installation.php) extension.
For more info visit the [Swoole website](https://www.swoole.co.uk/)
> NOTE: TRUE promise work only in CLI mode

```php
use Streamcommon\Promise\PromiseCo;

// be careful with this
\Swoole\Runtime::enableCoroutine(); // IF YOU WANT REALY ASYNC

$promise = PromiseCo::create(function (callable $resolve) {
    $resolve(41);
});
$promise->then(function ($value) {
    return $value + 1;
})->then(function ($value) {
    echo $value . PHP_EOL;
});
```

## Standard Promise
```php
use Streamcommon\Promise\Promise;

$promise = Promise::create(function (callable $resolve) {
    $resolve(41);
});
$promise->then(function ($value) {
    return $value + 1;
});
$promise->then(function ($value) {
    echo $value . PHP_EOL;
});
$promise->wait(); // Sync promise execution
```

[Master branch]: https://github.com/streamcommon/promise/tree/master
[Master branch image]: https://img.shields.io/badge/branch-master-blue.svg
[Develop branch]: https://github.com/streamcommon/promise/tree/develop
[Develop branch image]: https://img.shields.io/badge/branch-develop-blue.svg
[Master image]: https://travis-ci.org/streamcommon/promise.svg?branch=master
[Master]: https://travis-ci.org/streamcommon/promise
[Master coverage image]: https://coveralls.io/repos/github/streamcommon/promise/badge.svg?branch=master
[Master coverage]: https://coveralls.io/github/streamcommon/promise?branch=master
[Develop image]: https://travis-ci.org/streamcommon/promise.svg?branch=develop
[Develop]: https://travis-ci.org/streamcommon/promise
[Develop coverage image]: https://coveralls.io/repos/github/streamcommon/promise/badge.svg?branch=develop
[Develop coverage]: https://coveralls.io/github/streamcommon/promise?branch=develop