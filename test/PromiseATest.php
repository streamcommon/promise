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

namespace Streamcommon\Test\Promise;

use PHPUnit\Framework\TestCase;
use Streamcommon\Promise\{PromiseA, PromiseInterface};
use Streamcommon\Promise\Exception\RuntimeException;

/**
 * Class PromiseATest
 *
 * @package Streamcommon\Test\Promise
 */
class PromiseATest extends TestCase
{
    /**
     * Test sync promise
     */
    public function testPromise(): void
    {
        $promise = PromiseA::create(function ($resolver) {
            $resolver(41);
        });
        $promise->then(function ($value) {
            return $value + 1;
        });
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }

    /**
     * Test sync promise
     */
    public function testPromiseResolve(): void
    {
        $promise = PromiseA::resolve(42);
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }


    /**
     * Test sync promise
     */
    public function testPromiseReject(): void
    {
        $promise = PromiseA::reject(42);
        $promise->then(null, function ($value) {
            $this->assertEquals(42, $value);
        });
    }

    /**
     * Test throw
     */
    public function testPromiseThrow(): void
    {
        $promise = PromiseA::create(function ($resolver) {
            throw new RuntimeException();
        });
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }

    /**
     * Test sub promise
     */
    public function testSubPromise(): void
    {
        $promise = PromiseA::create(function (callable $resolve) {
            $promise = PromiseA::create(function (callable $resolve) {
                $resolve(41);
            });
            $promise->then(function ($value) use ($resolve) {
                $resolve($value);
            });
        });
        $promise->then(function ($value) {
            return PromiseA::create(function (callable $resolve) use ($value) {
                $resolve($value + 1);
            });
        });
        $promise->then(function ($value) {
            return PromiseA::create(function (callable $resolve) use ($value) {
                $resolve($value + 1);
            })->then(function ($value) {
                return $value + 1;
            });
        });
        $promise->then(function ($value) {
            $this->assertEquals(44, $value);
        });
    }

    /**
     * Test sub promise instance exception
     */
    public function testSubPromiseException(): void
    {
        $promise = PromiseA::create(function (callable $resolve) {
            $resolve(new class implements PromiseInterface {
                public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
                {
                }

                public static function create(callable $promise): PromiseInterface
                {
                }

                public static function resolve($value): PromiseInterface
                {
                }

                public static function reject($value): PromiseInterface
                {
                }

            });
        });
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }
}