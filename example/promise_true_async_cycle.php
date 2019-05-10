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
########### INIT ##############
$promise1 = PromiseA::create(function (callable $resolve) {
    $resolve(41);
});
$promise2 = $promise1->then(function ($value) {
    sleep(2);
    return $value + 1;
});
$promise3 = $promise1->then(function ($value) {
    sleep(1);
    throw new \Exception('error');
});
$promise4 = $promise1->then(function ($value) {
    return PromiseA::create(function (callable $resolver) use ($value) {
        sleep(3);
        $resolver($value + 5);
    });
});
########### INIT ##############
$c = 0;
while (true) {
    if ($c > 0) {
        continue;
    }
    ########### RESULT ##############
    $promise1->then(function ($value) {
        echo $value . ' === 41' . PHP_EOL;
    });
    $promise2->then(function ($value) {
        echo $value . ' === 42' . PHP_EOL;
    });
    $promise3->then(null, function ($error) {
        echo 'instanceof Throwable === ' . ($error instanceof Throwable) . PHP_EOL;
    });
    $promise4->then(function ($value) {
        echo $value . ' === 46' . PHP_EOL;
    });
    ########### RESULT ##############
    $c++;
    $promise1->wait(); // IF USE DAEMON MODE
}
