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

/**
 * Class AbstractPromise
 *
 * @package Streamcommon\Promise
 */
abstract class AbstractPromise implements PromiseInterface
{
    const STATE_PENDING   = 1;
    const STATE_FULFILLED = 0;
    const STATE_REJECTED  = -1;

    /** @var int */
    protected $state = self::STATE_PENDING;

    /**
     * AbstractPromise constructor
     *
     * @param callable $executor
     */
    abstract public function __construct(callable $executor);

    /**
     * {@inheritDoc}
     *
     * @param callable $promise
     * @return PromiseInterface
     */
    final public static function create(callable $promise): PromiseInterface
    {
        return new static($promise);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     * @return PromiseInterface
     */
    final public static function resolve($value): PromiseInterface
    {
        return new static(function (callable $resolve) use ($value) {
            $resolve($value);
        });
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $value
     * @return PromiseInterface
     */
    final public static function reject($value): PromiseInterface
    {
        return new static(function (callable $resolve, callable $reject) use ($value) {
            $reject($value);
        });
    }

    /**
     * {@inheritDoc}
     *
     * @param callable $onRejected
     * @return PromiseInterface
     */
    final public function catch(callable $onRejected): PromiseInterface
    {
        return $this->then(null, $onRejected);
    }

    /**
     * Change promise state
     *
     * @param integer $state
     * @return void
     */
    final protected function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Promise is pending
     *
     * @return boolean
     */
    final protected function isPending(): bool
    {
        return $this->state == self::STATE_PENDING;
    }

    /**
     * Promise is fulfilled
     *
     * @return boolean
     */
    final protected function isFulfilled(): bool
    {
        return $this->state == self::STATE_FULFILLED;
    }
}
