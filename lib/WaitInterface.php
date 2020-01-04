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

/**
 * Interface WaitInterface
 *
 * @package Streamcommon\Promise
 */
interface WaitInterface
{
    /**
     * Expect promise
     *
     * @return void
     */
    public function wait(): void;
}