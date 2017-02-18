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

namespace Acconplish\CustomSentinel\Activations;

use Acconplish\CustomSentinel\Users\UserInterface;

interface ActivationRepositoryInterface
{
    /**
     * Create a new activation record and code.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return \Acconplish\CustomSentinel\Activations\ActivationInterface
     */
    public function create(UserInterface $user);

    /**
     * Checks if a valid activation for the given user exists.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @param  string  $code
     * @return \Acconplish\CustomSentinel\Activations\ActivationInterface|bool
     */
    public function exists(UserInterface $user, $code = null);

    /**
     * Completes the activation for the given user.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @param  string  $code
     * @return bool
     */
    public function complete(UserInterface $user, $code);

    /**
     * Checks if a valid activation has been completed.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return \Acconplish\CustomSentinel\Activations\ActivationInterface|bool
     */
    public function completed(UserInterface $user);

    /**
     * Remove an existing activation (deactivate).
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return bool|null
     */
    public function remove(UserInterface $user);

    /**
     * Remove expired activation codes.
     *
     * @return int
     */
    public function removeExpired();
}
