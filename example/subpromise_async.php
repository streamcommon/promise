<?php
/**
 * This file is part of the Common package, a StreamCommon open software project.
 *
 * @copyright (c) 2019 StreamCommon Team.
 * @see https://github.com/streamcommon/promise
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

use Streamcommon\Promise\PromiseA;

if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
    echo 'PromiseCo MUST running only in CLI mode with swoole extension' . PHP_EOL;
    exit(0);
}
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    throw new \RuntimeException('File autoload.php not exists');
}
#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $promise = PromiseA::create(function (callable $resolve) {
        $resolve(41);
    });
    $promise->then(function ($value) use ($resolve) {
        $resolve($value);
    });
});
$promise2 = $promise->then(function ($value) {
    sleep(3);
    return $value + 1;
});
$promise->then(function ($value) {
    echo $value . ' === 41' . PHP_EOL;
});
$promise2->then(function ($value) {
    echo $value . ' === 42' . PHP_EOL;
});
#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $resolve(PromiseA::create(function (callable $resolve) {
        sleep(2);
        $resolve(42);
    }));
});
$promise2 = $promise->then(function ($value) {
    return $value + 1;
});
$promise->then(function ($value) {
    sleep(1);
    echo $value . ' === 42' . PHP_EOL;
});
$promise2->then(function ($value) {
    echo $value . ' === 43' . PHP_EOL;
});
#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $resolve(43);
});
$promise2 = $promise->then(function ($value) {
    sleep(1);
    return PromiseA::create(function (callable $resolve) use ($value) {
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
#############################################################
