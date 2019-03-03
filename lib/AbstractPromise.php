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

/**
 * Class AbstractPromise
 *
 * @package Streamcommon\Promise
 */
abstract class AbstractPromise implements PromiseInterface
{
    /** @var int */
    private $state = PromiseInterface::STATE_PENDING;

    /**
     * AbstractPromise constructor.
     *
     * @param callable $promise
     */
    public function __construct(callable $promise)
    {
    }

    /**
     * This method create new promise instance
     *
     * @param callable $promise
     * @return PromiseCo
     */
    public static function create(callable $promise): AbstractPromise
    {
        return new static($promise);
    }

    /**
     * Change promise state
     *
     * @param int $state
     */
    final protected function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Promise is fulfilled
     *
     * @return bool
     */
    final protected function isFulfilled(): bool
    {
        return $this->state === PromiseInterface::STATE_FULFILLED;
    }

    /**
     * Promise is rejected
     *
     * @return bool
     */
    final protected function isRejected(): bool
    {
        return $this->state === PromiseInterface::STATE_REJECTED;
    }
}
