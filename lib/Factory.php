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

use function extension_loaded;
use const PHP_SAPI;

/**
 * Class Factory
 * @package Streamcommon\Promise
 *
 * @codeCoverageIgnore
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
                return ExtSwoolePromise::create($promise);
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
                return ExtSwoolePromise::resolve($value);
            }
        }
        return Promise::create($value);
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
                return ExtSwoolePromise::resolve($value);
            }
        }
        return Promise::create($value);
    }
}
