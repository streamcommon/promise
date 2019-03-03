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

/**
 * Class Promise
 *
 * @package Streamcommon\PromiseCo
 *
 * @todo what about free channel???
 */
final class PromiseCo extends AbstractPromise
{
    /** @var Channel */
    protected $sequenceSet;

    /**
     * PromiseCo constructor.
     *
     * @param callable $promise
     */
    public function __construct(callable $promise)
    {
        if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
            throw new Exception\RuntimeException(
                'PromiseCo MUST running only in CLI mode with swoole extension'
            );
        }
        $this->sequenceSet = new Channel();
        Coroutine::create(function (callable $promise) {
            try {
                $resolve = function ($value) {
                    $this->setState(PromiseInterface::STATE_FULFILLED);
                    $this->sequenceSet->push($value, 60);
                };
                $reject = function ($value) {
                    $this->setState(PromiseInterface::STATE_REJECTED);
                    $this->sequenceSet->push($value, 60);
                };
                $promise($resolve, $reject);
            } catch (\Throwable $exception) {
                $this->setState(PromiseInterface::STATE_REJECTED);
                $this->sequenceSet->push($exception, 60);
            }
        }, $promise);
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
     *
     * @param callable|null $onFulfilled
     * @param callable|null $onRejected
     * @return PromiseInterface
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        Coroutine::create(function (array $callable) {
            list($onFulfilled, $onRejected) = $callable;
            Coroutine::defer(function () use ($onFulfilled, $onRejected) {
                $value = $this->sequenceSet->pop();
                $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
                $this->sequenceSet->push($callable($value));
            });
        }, [$onFulfilled, $onRejected]);
        return $this;
    }
}
