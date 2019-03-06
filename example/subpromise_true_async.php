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
\Swoole\Runtime::enableCoroutine();

#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $promise = PromiseA::create(function (callable $resolve) {
        $resolve(41);
    });
    $promise->then(function ($value) use ($resolve) {
        sleep(5); // check async
        $resolve($value);
    });
});
$promise->then(function ($value) {
    return $value + 1;
})->then(function ($value) {
    echo $value . PHP_EOL;
});
#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $resolve(PromiseA::create(function (callable $resolve) {
        $resolve(42);
    }));
});
$promise->then(function ($value) {
    return $value + 1;
});
$promise->then(function ($value) {
    echo $value . PHP_EOL;
});
#############################################################

#############################################################
$promise = PromiseA::create(function (callable $resolve) {
    $resolve(43);
});
$promise->then(function ($value) {
    return PromiseA::create(function (callable $resolve) use ($value) {
        sleep(3); // check async
        $resolve($value + 1);
    });
});
$promise->then(function ($value) {
    echo $value . PHP_EOL;
});
#############################################################
