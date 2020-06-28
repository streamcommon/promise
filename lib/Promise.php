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

/**
 * Class Promise
 *
 * @package Streamcommon\Promise
 */
final class Promise extends AbstractPromise implements WaitInterface
{
    /** @var ArrayCollection<int, PromiseInterface> */
    private $collection;
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
        $this->executor   = $executor;
        $this->collection = new ArrayCollection();
    }

    /**
     * It be called after promise change stage
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return PromiseInterface
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        /** @var Promise $promise */
        $promise = self::create(function (callable $resolve, callable $reject) use ($onFulfilled, $onRejected) {
            if ($this->isPending()) {
                $this->wait();
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
        $this->collection->add($promise);
        return $promise;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable|Promise[] $promises
     * @return PromiseInterface
     */
    public static function all(iterable $promises): PromiseInterface
    {
        return self::create(function (callable $resolve, callable $reject) use ($promises) {
            $result     = new ArrayCollection();
            $key        = 0;
            $firstError = null;

            foreach ($promises as $promise) {
                if (!$promise instanceof Promise) {
                    throw new Exception\RuntimeException('Supported only Streamcommon\Promise\Promise instance');
                }
                $promise->then(function ($value) use ($key, $result) {
                    $result->set($key, $value);
                }, function ($error) use (&$firstError) {
                    if ($firstError !== null) {
                        return;
                    }

                    $firstError = $error;
                });
                $promise->wait();
                $key++;
            }

            if ($firstError !== null) {
                $reject($firstError);
                return;
            }

            $resolve($result);
        });
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    public function wait(): void
    {
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
        try {
            ($this->executor)($resolve, $reject);
        } catch (\Throwable $exception) {
            $reject($exception);
        }
        foreach ($this->collection as $promise) {
            if ($promise instanceof WaitInterface) {
                $promise->wait();
            }
        }
        $this->collection->clear();
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
     * Destructor
     */
    public function __destruct()
    {
        $this->collection->clear();
        unset($this->collection);
    }
}
