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

use Ds\Queue as SequenceSet;
use Throwable;

use function call_user_func_array;

/**
 * Class Promise
 *
 * @package Streamcommon\Promise
 */
final class Promise extends AbstractPromise
{
    /** @var SequenceSet */
    protected $sequenceSet;
    /** @var callable */
    protected $promise;
    /** @var mixed */
    protected $value;

    /**
     * Promise constructor.
     *
     * @param callable $promise
     */
    public function __construct(callable $promise)
    {
        $this->promise = $promise;
        $this->sequenceSet = new SequenceSet();
    }

    /**
     * This method create new promise instance
     *
     * @param callable $promise
     * @return Promise
     */
    public static function create(callable $promise): Promise
    {
        return new static($promise);
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
        $this->sequenceSet->push([$onFulfilled, $onRejected]);
        return $this;
    }

    /**
     * Expect promise
     */
    public function wait(): void
    {
        try {
            $resolve = function ($value) {
                $this->setState(PromiseInterface::STATE_FULFILLED);
                $this->value = $value;
            };
            $reject = function ($value) {
                $this->setState(PromiseInterface::STATE_REJECTED);
                $this->value = $value;
            };
            call_user_func_array($this->promise, [$resolve, $reject]);
        } catch (Throwable $exception) {
            $this->setState(PromiseInterface::STATE_REJECTED);
            $this->value = $exception;
        }
        while ($this->value !== null) {
            $value = $this->value;
            $this->value = null;
            if (($callable = $this->sequenceSet->pop()) !== null) {
                list($onFulfilled, $onRejected) = $callable;
                $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
                $this->value = $callable($value);
            }
        }
    }
}