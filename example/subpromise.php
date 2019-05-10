<?php
/**
 * This file is part of the Promise package, a StreamCommon open software project.
 *
 * @copyright (c) 2019 StreamCommon Team.
 * @see https://github.com/streamcommon/promise
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

use Streamcommon\Promise\Promise;

if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    throw new \RuntimeException('File autoload.php not exists');
}
#############################################################

#############################################################
$promise = Promise::create(function (callable $resolve) {
    $promise = Promise::create(function (callable $resolve) {
        $resolve(41);
    });
    $promise->then(function ($value) use ($resolve) {
        $resolve($value);
    });
    $promise->wait();
});
$promise2 = $promise->then(function ($value) {
    return $value + 1;
});
$promise->then(function ($value) {
    echo $value . ' === 41' . PHP_EOL;
});
$promise2->then(function ($value) {
    echo $value . ' === 42' . PHP_EOL;
});
$promise->wait();
#############################################################

#############################################################
$promise = Promise::create(function (callable $resolve) {
    $resolve(Promise::create(function (callable $resolve) {
        $resolve(42);
    }));
});
$promise2 = $promise->then(function ($value) {
    return $value + 1;
});
$promise->then(function ($value) {
    echo $value . ' === 42' . PHP_EOL;
});
$promise2->then(function ($value) {
    echo $value . ' === 43' . PHP_EOL;
});
$promise->wait();
#############################################################

#############################################################
$promise = Promise::create(function (callable $resolve) {
    $resolve(43);
});
$promise2 = $promise->then(function ($value) {
    // HAVE BIG PROBLEM
    return Promise::create(function (callable $resolve) use ($value) {
        $resolve($value + 1);
    })->then(function ($value) {
        return $value + 1;
    });
});
$promise->then(function ($value) {
    echo $value . ' === 43' . PHP_EOL;
});
$promise2->then(function ($value) {
    echo $value . ' === 45' . PHP_EOL;
});
$promise->wait();
#############################################################
