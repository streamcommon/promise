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
use Streamcommon\Promise\Promise;
use Streamcommon\Promise\Exception\RuntimeException;

/**
 * Class PromiseTest
 *
 * @package Streamcommon\Test\Promise
 */
class PromiseTest extends TestCase
{
    /**
     * Test sync promise
     */
    public function testPromise(): void
    {
        $promise = Promise::create(function ($resolver) {
            $resolver(42);
        });
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
        $promise->wait();
    }

    /**
     * Test sync promise
     */
    public function testPromiseResolve(): void
    {
        $promise = Promise::resolve(42);
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
        $promise->wait();
    }


    /**
     * Test sync promise
     */
    public function testPromiseReject(): void
    {
        $promise = Promise::reject(42);
        $promise->then(null, function ($value) {
            $this->assertEquals(42, $value);
        });
        $promise->wait();
    }

    /**
     * Test throw
     */
    public function testPromiseThrow(): void
    {
        $promise = Promise::create(function ($resolver) {
            throw new RuntimeException();
        });
        $promise->then(null, function ($value) {
            $this->assertInstanceOf(RuntimeException::class, $value);
        });
        $promise->wait();
    }
}