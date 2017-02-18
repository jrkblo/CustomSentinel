<?php

/**
 * Part of the Sentinel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Sentinel
 * @version    2.0.14
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2017, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Acconplish\CustomSentinel\Throttling;

use Acconplish\CustomSentinel\Users\UserInterface;

interface ThrottleRepositoryInterface
{
    /**
     * Returns the global throttling delay, in seconds.
     *
     * @return int
     */
    public function globalDelay();

    /**
     * Returns the IP address throttling delay, in seconds.
     *
     * @param  string  $ipAddress
     * @return int
     */
    public function ipDelay($ipAddress);

    /**
     * Returns the throttling delay for the given user, in seconds.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return int
     */
    public function userDelay(UserInterface $user);

    /**
     * Logs a new throttling entry.
     *
     * @param  string  $ipAddress
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return void
     */
    public function log($ipAddress = null, UserInterface $user = null);
}