<?php

namespace Streamcommon\Promise;

use function extension_loaded;
use const PHP_SAPI;

/**
 * Class Factory
 * @package Streamcommon\Promise
 */
class Factory
{
    /**
     * This method MUST create new promise instance
     *
     * @param callable $promise
     * @return PromiseInterface
     */
    public static function create(callable $promise): PromiseInterface
    {
        if (PHP_SAPI == 'cli') {
            if (extension_loaded('swoole')) {
                return PromiseA::create($promise);
            }
        }
        return Promise::create($promise);
    }

    /**
     * This method create new fulfilled promise with $value result
     *
     * @param mixed $value
     * @return PromiseInterface
     */
    public static function resolve($value): PromiseInterface
    {
        if (PHP_SAPI == 'cli') {
            if (extension_loaded('swoole')) {
                return PromiseA::resolve($value);
            }
        }
        return PromiseA::create($value);
    }

    /**
     * This method create new rejected promise with $value result
     *
     * @param mixed $value
     * @return PromiseInterface
     */
    public static function reject($value): PromiseInterface
    {
        if (PHP_SAPI == 'cli') {
            if (extension_loaded('swoole')) {
                return PromiseA::resolve($value);
            }
        }
        return PromiseA::create($value);
    }
}
