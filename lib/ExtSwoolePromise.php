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

namespace Streamcommon\Promise;

use Doctrine\Common\Collections\ArrayCollection;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

use function extension_loaded;
use function count;
use function is_callable;
use function usleep;
use const PHP_SAPI;

/**
 * Class ExtSwoolePromise
 *
 * @package Streamcommon\Promise
 */
final class ExtSwoolePromise extends AbstractPromise
{
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
        if (PHP_SAPI != 'cli' || !extension_loaded('swoole')) {
            throw new Exception\RuntimeException(
                'ExtSwoolePromise MUST running only in CLI mode with swoole extension.'
            );
        }
        // @codeCoverageIgnoreEnd
        $resolve = function ($value) {
            $this->setResult($value);
            $this->setState(self::STATE_FULFILLED);
        };
        $reject  = function ($value) {
            if ($this->isPending()) {
                $this->setResult($value);
                $this->setState(self::STATE_REJECTED);
            }
        };
        Coroutine::create(function (callable $executor, callable $resolve, callable $reject) {
            try {
                $executor($resolve, $reject);
            } catch (\Throwable $exception) {
                $reject($exception);
            }
        }, $executor, $resolve, $reject);
    }

    /**
     * {@inheritDoc}
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return ExtSwoolePromise
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        return self::create(function (callable $resolve, callable $reject) use ($onFulfilled, $onRejected) {
            while ($this->isPending()) {
                // @codeCoverageIgnoreStart
                usleep(PROMISE_WAIT);
                // @codeCoverageIgnoreEnd
            }
            $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
            if (!is_callable($callable)) {
                $resolve($this->result);
                return;
            }
            try {
                $resolve($callable($this->result));
            } catch (\Throwable $error) {
                $reject($error);
            }
        });
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable|ExtSwoolePromise[] $promises
     * @return ExtSwoolePromise
     */
    public static function all(iterable $promises): PromiseInterface
    {
        return self::create(function (callable $resolve) use ($promises) {
            $ticks   = count($promises);
            $channel = new Channel($ticks);
            $result  = new ArrayCollection();
            $key     = 0;
            foreach ($promises as $promise) {
                if (!$promise instanceof ExtSwoolePromise) {
                    $channel->close();
                    throw new Exception\RuntimeException(
                        'Supported only Streamcommon\Promise\ExtSwoolePromise instance'
                    );
                }
                $promise->then(function ($value) use ($key, $result, $channel) {
                    $result->set($key, $value);
                    $channel->push(true);
                    return $value;
                });
                $key++;
            }
            while ($ticks--) {
                $channel->pop();
            }
            $channel->close();
            $resolve($result);
        });
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
            if (!$value instanceof ExtSwoolePromise) {
                throw new Exception\RuntimeException('Supported only Streamcommon\Promise\ExtSwoolePromise instance');
            }
            $resolved = false;
            $callable = function ($value) use (&$resolved) {
                $this->setResult($value);
                $resolved = true;
            };
            $value->then($callable, $callable);
            // resolve async locking error
            while (!$resolved) {
                // @codeCoverageIgnoreStart
                usleep(PROMISE_WAIT);
                // @codeCoverageIgnoreEnd
            }
        } else {
            $this->result = $value;
        }
    }
}
