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

namespace Streamcommon\Promise;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

use function extension_loaded;

/**
 * Class PromiseA
 *
 * @package Streamcommon\Promise
 */
final class PromiseA implements PromiseInterface
{
    /** @var int */
    private $state = PromiseInterface::STATE_PENDING;
    /** @var Channel */
    private $sequenceSet;

    /**
     * PromiseCo constructor.
     *
     * @param callable $promise
     */
    public function __construct(callable $promise)
    {
        // @codeCoverageIgnoreStart
        if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
            throw new Exception\RuntimeException(
                'PromiseCo MUST running only in CLI mode with swoole extension'
            );
        }
        // @codeCoverageIgnoreEnd
        $this->sequenceSet = new Channel();
        Coroutine::create(function (callable $promise) {
            try {
                $resolve = function ($value) {
                    $this->setState(PromiseInterface::STATE_FULFILLED);
                    $this->setResult($value);
                };
                $reject = function ($value) {
                    $this->setState(PromiseInterface::STATE_REJECTED);
                    $this->setResult($value);
                };
                $promise($resolve, $reject);
            } catch (Throwable $exception) {
                $this->setState(PromiseInterface::STATE_REJECTED);
                $this->setResult($exception);
            }
        }, $promise);
    }

    /**
     * This method create new promise instance
     *
     * @param callable $promise
     * @return PromiseA
     */
    public static function create(callable $promise): PromiseInterface
    {
        return new static($promise);
    }

    /**
     * This method create new fulfilled promise with $value result
     *
     * @param mixed $value
     * @return PromiseA
     */
    public static function resolve($value): PromiseInterface
    {
        return new static(function (callable $resolve) use ($value) {
            $resolve($value);
        });
    }

    /**
     * This method create new rejected promise with $value result
     *
     * @param mixed $value
     * @return PromiseA
     */
    public static function reject($value): PromiseInterface
    {
        return new static(function (callable $resolve, callable $reject) use ($value) {
            $reject($value);
        });
    }

    /**
     * It be called after promise change stage
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return PromiseA
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        Coroutine::create(function (array $callable) {
            list($onFulfilled, $onRejected) = $callable;
            Coroutine::defer(function () use ($onFulfilled, $onRejected) {
                if (($value = $this->sequenceSet->pop()) !== null) {
                    $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
                    $value = $callable($value);
                    if ($value !== null) {
                        $this->setResult($value);
                    } else {
                        $this->sequenceSet->close();
                    }
                }
            });
        }, [$onFulfilled, $onRejected]);
        return $this;
    }

    /**
     * Change promise state
     *
     * @param int $state
     */
    private function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Set resolved result
     *
     * @param mixed $value
     */
    private function setResult($value): void
    {
        if ($value instanceof PromiseInterface) {
            if (($value instanceof PromiseA) === false) {
                throw new Exception\RuntimeException('Supported only Streamcommon\Promise\PromiseA instance');
            }
            $value->then(function ($value) {
                $this->setResult($value);
            });
        } else {
            $this->sequenceSet->push($value, 60);
        }
    }

    /**
     * Promise is fulfilled
     *
     * @return bool
     */
    private function isFulfilled(): bool
    {
        return $this->state === PromiseInterface::STATE_FULFILLED;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->sequenceSet->close();
        unset($this->sequenceSet);
    }
}
