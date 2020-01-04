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

namespace Streamcommon\Promise;

use parallel\{Runtime, Sync};

use function realpath;
use function file_exists;
use function is_callable;
use function json_encode;
use function is_string;
use function json_decode;

/**
 * Class ExtSwoolePromise
 *
 * @package Streamcommon\Promise
 */
final class ExtParallelPromise extends AbstractPromise
{
    /** @var Sync */
    private $sync;
    /** @var mixed */
    private $result;

    /**
     * PromiseCo constructor.
     *
     * @param callable $executor
     */
    public function __construct(callable $executor)
    {
        // @codeCoverageIgnoreStart
        if (PHP_SAPI != 'cli' || !extension_loaded('parallel')) {
            throw new Exception\RuntimeException(
                'ExtParallelPromise MUST running only in CLI mode with parallel extension.'
            );
        }
        // @codeCoverageIgnoreEnd
        $autoloadFiles = [
            realpath(__DIR__) . '/../vendor/autoload.php',
            realpath(__DIR__) . '/../../../autoload.php'
        ];
        $bootstrap = null;
        foreach ($autoloadFiles as $autoloadFile) {
            if (file_exists($autoloadFile)) {
                $bootstrap = $autoloadFile;
                break;
            }
        }
        $this->sync = $sync = new Sync;
        $resolve = function ($value) use ($sync) {
            $this->setResult($value);
            $this->setState(self::STATE_FULFILLED);
            $sync->set(json_encode([
                'result' => $this->result,
                'state'  => $this->state,
            ]));
        };
        $reject = function ($value) use ($sync) {
            if ($this->isPending()) {
                $this->setResult($value);
                $this->setState(self::STATE_REJECTED);
                $sync->set(json_encode([
                    'result' => $this->result,
                    'state'  => $this->state,
                ]));
            }
        };
        (new Runtime($bootstrap))->run(function (callable $executor, callable $resolve, callable $reject) use ($sync) {
            $sync(function () use ($sync, $executor, $resolve, $reject) {
                try {
                    $executor($resolve, $reject);
                } catch (\Throwable $exception) {
                    $reject($exception);
                }
            });
        }, [$executor, $resolve, $reject]);
    }

    /**
     * {@inheritDoc}
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return ExtParallelPromise
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        $sync = $this->sync;
        return self::create(function (callable $resolve, callable $reject) use ($onFulfilled, $onRejected, $sync) {
            $sync(function () use ($sync, $resolve, $reject, $onFulfilled, $onRejected) {
                while (!is_string($value = $sync->get())) {
                    $sync->wait();
                }
                $value = json_decode($value);
                $callable = $value->state == self::STATE_FULFILLED ? $onFulfilled : $onRejected;
                if (!is_callable($callable)) {
                    $resolve($value->result);
                    return;
                }
                try {
                    $resolve($callable($value->result));
                } catch (\Throwable $error) {
                    $reject($error);
                }
            });
        });
    }


    /**
     * {@inheritDoc}
     *
     * @param iterable<ExtParallelPromise> $promises
     * @return ExtParallelPromise
     */
    public static function all(iterable $promises): PromiseInterface
    {
        //todo
    }

    /**
     * Set resolved result
     *
     * @param mixed $value
     * @return void
     */
    private function setResult($value): void
    {
        if ($value instanceof PromiseInterface) {
            if (!$value instanceof ExtParallelPromise) {
                throw new Exception\RuntimeException('Supported only Streamcommon\Promise\ExtParallelPromise instance');
            }

            //todo
        } else {
             //var_dump($value);
            $this->result = $value;
        }
    }
}