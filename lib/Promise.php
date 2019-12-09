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

use Ds\{Map, Stack};
use Throwable;

/**
 * Class Promise
 *
 * @package Streamcommon\Promise
 */
final class Promise implements PromiseInterface
{
    /** @var int */
    private $state = PromiseInterface::STATE_PENDING;
    /** @var Stack */
    private $stack;
    /** @var callable */
    private $executor;
    /** @var mixed */
    private $result;

    /**
     * Promise constructor.
     *
     * @param callable $executor
     */
    public function __construct(callable $executor)
    {
        $this->executor = $executor;
        $this->stack    = new Stack();
    }

    /**
     * This method create new promise
     *
     * @param callable $promise
     * @return Promise
     */
    public static function create(callable $promise): PromiseInterface
    {
        return new self($promise);
    }

    /**
     * This method create new fulfilled promise with $value result
     *
     * @param mixed $value
     * @return Promise
     */
    public static function resolve($value): PromiseInterface
    {
        return new self(function (callable $resolve) use ($value) {
            $resolve($value);
        });
    }

    /**
     * This method create new rejected promise with $value result
     *
     * @param mixed $value
     * @return Promise
     */
    public static function reject($value): PromiseInterface
    {
        return new self(function (callable $resolve, callable $reject) use ($value) {
            $reject($value);
        });
    }

    /**
     * It be called after promise change stage
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return Promise
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        $promise = self::create(function (callable $resolve, callable $reject) use ($onFulfilled, $onRejected) {
            if ($this->state == PromiseInterface::STATE_PENDING) {
                $this->wait();
            }
            $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
            if (!is_callable($callable)) {
                $resolve($this->result);
                return;
            }
            try {
                $resolve($callable($this->result));
            } catch (Throwable $error) {
                $reject($error);
            }
        });
        $this->stack->push($promise);
        return $promise;
    }

    /**
     * {@inheritDoc}
     *
     * @param callable $onRejected
     * @return Promise
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        return $this->then(null, $onRejected);
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable $promises
     * @return Promise
     */
    public static function all(iterable $promises): PromiseInterface
    {
        return self::create(function (callable $resolve) use ($promises) {
            $map = new Map();
            foreach ($promises as $key => $promise) {
                if (!$promise instanceof Promise) {
                    throw new Exception\RuntimeException('Supported only Streamcommon\Promise\Promise instance');
                }
                $promise->then(function ($value) use ($key, $map) {
                    $map->put($key, $value);
                    return $value;
                });
                $promise->wait();
            }
            $resolve($map);
        });
    }

    /**
     * Expect promise
     *
     * @return void
     */
    public function wait(): void
    {
        try {
            $resolve = function ($value) {
                $this->setResult($value);
                $this->setState(PromiseInterface::STATE_FULFILLED);
            };
            $reject  = function ($value) {
                $this->setResult($value);
                $this->setState(PromiseInterface::STATE_REJECTED);
            };
            ($this->executor)($resolve, $reject);
        } catch (Throwable $exception) {
            if ($this->state == PromiseInterface::STATE_PENDING) {
                $this->result = $exception;
                $this->setState(PromiseInterface::STATE_REJECTED);
            }
        }
        while (!$this->stack->isEmpty()) {
            $promise = $this->stack->pop();
            if ($promise instanceof Promise) {
                $promise->wait();
            }
        }
        $this->stack->clear();
    }

    /**
     * Change promise state
     *
     * @param integer $state
     * @return void
     */
    private function setState(int $state): void
    {
        $this->state = $state;
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
            if (!$value instanceof Promise) {
                throw new Exception\RuntimeException('Supported only Streamcommon\Promise\Promise instance');
            }
            $callable = function ($value) {
                $this->setResult($value);
            };
            $value->then($callable, $callable);
            $value->wait();
        } else {
            $this->result = $value;
        }
    }

    /**
     * Promise is fulfilled
     *
     * @return boolean
     */
    private function isFulfilled(): bool
    {
        return $this->state == PromiseInterface::STATE_FULFILLED;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->stack->clear();
        unset($this->stack);
    }
}
