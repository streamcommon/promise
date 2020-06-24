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

namespace Streamcommon\Test\Promise;

use PHPUnit\Framework\TestCase;
use Streamcommon\Promise\{Promise, ExtSwoolePromise, PromiseInterface};
use Streamcommon\Promise\Exception\RuntimeException;

/**
 * Class ExtSwoolePromiseTest
 *
 * @package Streamcommon\Test\Promise
 */
class ExtSwoolePromiseTest extends TestCase
{
    /**
     * Test sync promise
     *
     * @return void
     */
    public function testPromise(): void
    {
        $promise  = ExtSwoolePromise::create(function ($resolver) {
            $resolver(41);
        });
        $promise2 = $promise->then(function ($value) {
            return $value + 1;
        });
        $promise3 = $promise->then();
        $promise->then(function ($value) {
            $this->assertEquals(41, $value);
        });
        $promise2->then(function ($value) {
            $this->assertEquals(43, $value);
        });
        $promise3->then(function ($value) {
            $this->assertNull($value);
        });
    }

    /**
     * Test sync promise
     *
     * @return void
     */
    public function testPromiseResolve(): void
    {
        $promise = ExtSwoolePromise::resolve(42);
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }


    /**
     * Test sync promise
     *
     * @return void
     */
    public function testPromiseReject(): void
    {
        $promise = ExtSwoolePromise::reject(42);
        $promise->then(null, function ($value) {
            $this->assertEquals(42, $value);
        });
    }

    /**
     * Test throw
     *
     * @return void
     */
    public function testPromiseThrow(): void
    {
        $promise = ExtSwoolePromise::create(function ($resolver) {
            throw new RuntimeException();
        });
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }

    /**
     * Test sub promise
     *
     * @return void
     */
    public function testSubPromise(): void
    {
        $promise  = ExtSwoolePromise::create(function (callable $resolve) {
            $promise = ExtSwoolePromise::create(function (callable $resolve) {
                $resolve(41);
            });
            $promise->then(function ($value) use ($resolve) {
                $resolve($value);
            });
        });
        $promise2 = $promise->then(function ($value) {
            return ExtSwoolePromise::create(function (callable $resolve) use ($value) {
                $resolve($value + 1);
            });
        });
        $promise3 = $promise->then(function ($value) {
            return ExtSwoolePromise::create(function (callable $resolve) use ($value) {
                $resolve($value + 1);
            })->then(function ($value) {
                return $value + 1;
            });
        });
        $promise->then(function ($value) {
            $this->assertEquals(41, $value);
        });
        $promise2->then(function ($value) {
            $this->assertEquals(42, $value);
        });
        $promise3->then(function ($value) {
            $this->assertEquals(43, $value);
        });
    }

    /**
     * Test sub promise instance exception
     *
     * @return void
     */
    public function testSubPromiseException(): void
    {
        $promise = ExtSwoolePromise::create(function (callable $resolve) {
            $resolve(new class implements PromiseInterface {
                /**
                 * @param callable|null $onFulfilled
                 * @param callable|null $onRejected
                 * @return PromiseInterface
                 */
                public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
                {
                    return $this;
                }

                /**
                 * @param callable $onRejected
                 * @return PromiseInterface
                 */
                public function catch(callable $onRejected): PromiseInterface
                {
                    return $this->then(null, $onRejected);
                }

                /**
                 * @param callable $promise
                 * @return PromiseInterface
                 */
                public static function create(callable $promise): PromiseInterface
                {
                    return new self();
                }

                /**
                 * @param mixed $value
                 * @return PromiseInterface
                 */
                public static function resolve($value): PromiseInterface
                {
                    return new self();
                }

                /**
                 * @param mixed $value
                 * @return PromiseInterface
                 */
                public static function reject($value): PromiseInterface
                {
                    return new self();
                }

                /**
                 * @param mixed $value
                 * @return PromiseInterface
                 */
                public static function all($value): PromiseInterface
                {
                    return new self();
                }

            });
        });
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }

    /**
     * Test promise all method
     *
     * @return void
     */
    public function testPromiseAll(): void
    {
        $promise1 = ExtSwoolePromise::create(function (callable $resolve) {
            $resolve(41);
        });
        $promise2 = ExtSwoolePromise::create(function (callable $resolve) {
            $resolve(42);
        });
        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise1, $promise2]);
        $promise->then(function ($value) {
            $this->assertIsArray($value);
        });
        $promise->then(function ($value) {
            $this->assertEquals([41, 42], $value);
        });
        $promise->then(function ($value) {
            $this->assertEquals(41, $value[0]);
            $this->assertEquals(42, $value[1]);
        });
    }

    /**
     * Test promise all method exception
     *
     * @return void
     */
    public function testPromiseAllException(): void
    {
        $promise1 = Promise::create(function (callable $resolve) {
            $resolve(41);
        });
        $promise2 = ExtSwoolePromise::create(function (callable $resolve) {
            $resolve(42);
        });
        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise1, $promise2]);
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }


    /**
     * Test promise all with failures
     *
     * @return void
     */
    public function testPromiseAnyFailure(): void
    {
        $promise1 = Promise::create(function (callable $resolve) {
            $resolve(41);
        });
        $promise2 = ExtSwoolePromise::create(function (callable $resolve, callable $reject) {
            $reject('A incidental error has occurred');
        });

        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise1, $promise2]);
        $promise->then(null, function ($value) {
            $this->assertEquals('A incidental error has occurred', $value);
        });
    }

    /**
     * Test promise all with a throwable failure which should be converted to a string
     *
     * @return void
     */
    public function testPromiseThrowableFailuresAreConverted(): void
    {
        $promise = ExtSwoolePromise::create(function (callable $resolve, callable $reject) {
            $reject(new RuntimeException('some exceptional message'));
        });

        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise]);
        $promise->then(null, function ($value) {
            $this->assertEquals('some exceptional message', $value);
        });
    }


    /**
     * Test promise all with non stringable  which should be converted to a string
     *
     * @return void
     */
    public function testPromiseNonStringableValuesAreConverted(): void
    {
        $promise = ExtSwoolePromise::create(function (callable $resolve, callable $reject) {
            $reject(new RuntimeException(['some exceptional message']));
        });

        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise]);
        $promise->then(null, function ($value) {
            $this->assertEquals(var_export('some exceptional message', true), $value);
        });
    }

    /**
     * Test promise all first error received is the error returned
     *
     * @return void
     */
    public function testPromiseAllMultipleErrorsWillStillOnlyReturnFirstFailure()
    {
        $promise1 = ExtSwoolePromise::create(function (callable $resolve, callable $reject) {
            $reject(new RuntimeException('some failing message'));
        });

        $promise2 = ExtSwoolePromise::create(function (callable $resolve, callable $reject) {
            $reject(new RuntimeException('this message is also failing but should not appear'));
        });

        /** @var ExtSwoolePromise $promise */
        $promise = ExtSwoolePromise::all([$promise1, $promise2]);
        $promise->then(null, function ($value) {
            $this->assertEquals('some failing message', $value);
        });
    }


    /**
     * Test promise catch
     *
     *
     * @return void
     */
    public function testPromiseCatch(): void
    {
        $promise = ExtSwoolePromise::create(function ($resolver) {
            throw new RuntimeException();
        });
        $promise->catch(function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
    }
}
