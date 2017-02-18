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

namespace Acconplish\CustomSentinel\Checkpoints;

use Acconplish\CustomSentinel\Users\UserInterface;
use RuntimeException;

class NotActivatedException extends RuntimeException
{
    /**
     * The user which caused the exception.
     *
     * @var \Acconplish\CustomSentinel\Users\UserInterface
     */
    protected $user;

    /**
     * Returns the user.
     *
     * @return \Acconplish\CustomSentinel\Users\UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user associated with Sentinel (does not log in).
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface
     * @return void
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }
}
