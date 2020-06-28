<?php
/**
 * This file is part of the Promise package, a StreamCommon open software project.
 *
 * @copyright (c) 2019-2020 StreamCommon
 * @see https://github.com/streamcommon/promise
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

use Streamcommon\Promise\ExtSwoolePromise;
use Swoole\Runtime;

if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
    echo 'ExtSwoolePromise MUST running only in CLI mode with swoole extension' . PHP_EOL;
    exit(0);
}
if (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';
} else {
    throw new \RuntimeException('File autoload.php not exists');
}
Runtime::enableCoroutine();
########### INIT ##############
$promise1      = ExtSwoolePromise::create(function (callable $resolve) {
    sleep(2);
    $resolve(41);
});
$promise2      = ExtSwoolePromise::create(function (callable $resolve) {
    $resolve(42);
});
$promisesArray = [$promise1, $promise2];

// @phpstan-ignore-next-line
$promise = ExtSwoolePromise::all([$promise1, $promise2]);
$promise->then(function ($value) {
    echo $value[0] . ' === 41' . PHP_EOL;
    echo $value[1] . ' === 42' . PHP_EOL;
});
