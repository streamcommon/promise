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

namespace Streamcommon\Test\Promise;

use PHPUnit\Framework\TestCase;
use Streamcommon\Promise\Factory;

/**
 * Class FactoryTest
 * @package Streamcommon\Test\Promise
 */
class FactoryTest extends TestCase
{
    /**
     * Factory create test
     *
     * @return void
     */
    public function testCreate(): void
    {
        $promise = Factory::create(function (callable $resolve, callable $reject) {
            $resolve(42);
        });
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }

    /**
     * Factory resolve test
     *
     * @return void
     */
    public function testResolve(): void
    {
        $promise = Factory::resolve(42);
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }

    /**
     * Factory reject test
     *
     * @return void
     */
    public function testReject(): void
    {
        $promise = Factory::reject(42);
        $promise->then(function ($value) {
            $this->assertEquals(42, $value);
        });
    }
}
