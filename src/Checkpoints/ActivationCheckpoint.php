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

use Acconplish\CustomSentinel\Activations\ActivationRepositoryInterface;
use Acconplish\CustomSentinel\Users\UserInterface;

class ActivationCheckpoint implements CheckpointInterface
{
    use AuthenticatedCheckpoint;

    /**
     * The activation repository.
     *
     * @var \Acconplish\CustomSentinel\Activations\ActivationRepositoryInterface
     */
    protected $activations;

    /**
     * Create a new activation checkpoint.
     *
     * @param  \Acconplish\CustomSentinel\Activations\ActivationRepositoryInterface  $activations
     * @return void
     */
    public function __construct(ActivationRepositoryInterface $activations)
    {
        $this->activations = $activations;
    }

    /**
     * {@inheritDoc}
     */
    public function login(UserInterface $user)
    {
        return $this->checkActivation($user);
    }

    /**
     * {@inheritDoc}
     */
    public function check(UserInterface $user)
    {
        return $this->checkActivation($user);
    }

    /**
     * Checks the activation status of the given user.
     *
     * @param  \Acconplish\CustomSentinel\Users\UserInterface  $user
     * @return bool
     * @throws \Acconplish\CustomSentinel\Checkpoints\NotActivatedException
     */
    protected function checkActivation(UserInterface $user)
    {
        $completed = $this->activations->completed($user);

        if (! $completed) {
            $exception = new NotActivatedException('Your account has not been activated yet.');

            $exception->setUser($user);

            throw $exception;
        }
    }
}
