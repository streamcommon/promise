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

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

use function extension_loaded;
use function count;
use function ksort;
use const PHP_SAPI;

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
    private $channel;
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
        if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
            throw new Exception\RuntimeException(
                'PromiseA MUST running only in CLI mode with swoole extension.'
            );
        }
        // @codeCoverageIgnoreEnd
        $this->channel = new Channel(1);
        Coroutine::create(function (callable $executor) {
            try {
                $resolve = function ($value) {
                    $this->setState(PromiseInterface::STATE_FULFILLED);
                    $this->setResult($value);
                };
                $reject  = function ($value) {
                    $this->setState(PromiseInterface::STATE_REJECTED);
                    $this->setResult($value);
                };
                $executor($resolve, $reject);
            } catch (Throwable $exception) {
                $this->setState(PromiseInterface::STATE_REJECTED);
                $this->setResult($exception);
            }
        }, $executor);
    }

    /**
     * This method create new promise instance
     *
     * @param callable $promise
     * @return PromiseA
     */
    public static function create(callable $promise): PromiseInterface
    {
        return new self($promise);
    }

    /**
     * This method create new fulfilled promise with $value result
     *
     * @param mixed $value
     * @return PromiseA
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
     * @return PromiseA
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
     * @return PromiseA
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        return self::create(function (callable $resolve, callable $reject) use ($onFulfilled, $onRejected) {
            $result = $this->channel->pop();
            $this->channel->push($result);
            $callable = $this->isFulfilled() ? $onFulfilled : $onRejected;
            if (!is_callable($callable)) {
                $resolve($result);
                return;
            }
            try {
                $resolve($callable($result));
            } catch (Throwable $error) {
                $reject($error);
            }
        });
    }

    /**
     * Returns a new promise when all promises are change stage
     *
     * @param iterable $promises
     * @return PromiseA
     */
    public static function all(iterable $promises): PromiseInterface
    {
        return self::create(function (callable $resolve) use ($promises) {
            $ticks   = count($promises);
            $channel = new Channel($ticks);
            $result  = [];
            foreach ($promises as $key => $promise) {
                if (!$promise instanceof PromiseA) {
                    $channel->close();
                    throw new Exception\RuntimeException('Supported only Streamcommon\Promise\PromiseA instance');
                }
                $promise->then(function ($value) use ($key, &$result, $channel) {
                    $result[$key] = $value;
                    $channel->push(true);
                    return $value;
                });
            }
            while ($ticks--) {
                $channel->pop();
            }
            $channel->close();
            ksort($result);
            $resolve($result);
        });
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
            if (!$value instanceof PromiseA) {
                throw new Exception\RuntimeException('Supported only Streamcommon\Promise\PromiseA instance');
            }
            $callable = function ($value) {
                $this->setResult($value);
            };
            $value->then($callable, $callable);
        } else {
            $this->result = $value;
            $this->channel->push($this->result);
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
        $this->channel->close();
        unset($this->channel);
    }
}
