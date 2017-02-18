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

namespace Acconplish\CustomSentinel\Laravel;

use Acconplish\CustomSentinel\Activations\IlluminateActivationRepository;
use Acconplish\CustomSentinel\Checkpoints\ActivationCheckpoint;
use Acconplish\CustomSentinel\Checkpoints\ThrottleCheckpoint;
use Acconplish\CustomSentinel\Cookies\IlluminateCookie;
use Acconplish\CustomSentinel\Hashing\NativeHasher;
use Acconplish\CustomSentinel\Persistences\IlluminatePersistenceRepository;
use Acconplish\CustomSentinel\Reminders\IlluminateReminderRepository;
use Acconplish\CustomSentinel\Roles\IlluminateRoleRepository;
use Acconplish\CustomSentinel\Sentinel;
use Acconplish\CustomSentinel\Sessions\IlluminateSession;
use Acconplish\CustomSentinel\Throttling\IlluminateThrottleRepository;
use Acconplish\CustomSentinel\Users\IlluminateUserRepository;
use Exception;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class SentinelServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $this->garbageCollect();
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->prepareResources();
        $this->setOverrides();
        $this->registerPersistences();
        $this->registerUsers();
        $this->registerRoles();
        $this->registerCheckpoints();
        $this->registerReminders();
        $this->registerSentinel();
        $this->setUserResolver();
    }

    /**
     * Prepare the package resources.
     *
     * @return void
     */
    protected function prepareResources()
    {
        // Publish config
        $config = realpath(__DIR__.'/../config/config.php');

        $this->mergeConfigFrom($config, 'acconplish.customsentinel');

        $this->publishes([
            $config => config_path('acconplish.customsentinel.php'),
        ], 'config');

        // Publish migrations
        $migrations = realpath(__DIR__.'/../migrations');

        $this->publishes([
            $migrations => $this->app->databasePath().'/migrations',
        ], 'migrations');
    }

    /**
     * Registers the persistences.
     *
     * @return void
     */
    protected function registerPersistences()
    {
        $this->registerSession();
        $this->registerCookie();

        $this->app->singleton('customsentinel.persistence', function ($app) {
            $config = $app['config']->get('acconplish.customsentinel.persistences');

            return new IlluminatePersistenceRepository(
                $app['customsentinel.session'], $app['customsentinel.cookie'], $config['model'], $config['single']
            );
        });
    }

    /**
     * Registers the session.
     *
     * @return void
     */
    protected function registerSession()
    {
        $this->app->singleton('customsentinel.session', function ($app) {
            return new IlluminateSession(
                $app['session.store'], $app['config']->get('acconplish.customsentinel.session')
            );
        });
    }

    /**
     * Registers the cookie.
     *
     * @return void
     */
    protected function registerCookie()
    {
        $this->app->singleton('customsentinel.cookie', function ($app) {
            return new IlluminateCookie(
                $app['request'], $app['cookie'], $app['config']->get('acconplish.customsentinel.cookie')
            );
        });
    }

    /**
     * Registers the users.
     *
     * @return void
     */
    protected function registerUsers()
    {
        $this->registerHasher();

        $this->app->singleton('customsentinel.users', function ($app) {
            $config = $app['config']->get('acconplish.customsentinel.users');

            return new IlluminateUserRepository(
                $app['customsentinel.hasher'], $app['events'], $config['model']
            );
        });
    }

    /**
     * Registers the hahser.
     *
     * @return void
     */
    protected function registerHasher()
    {
        $this->app->singleton('customsentinel.hasher', function () {
            return new NativeHasher;
        });
    }

    /**
     * Registers the roles.
     *
     * @return void
     */
    protected function registerRoles()
    {
        $this->app->singleton('customsentinel.roles', function ($app) {
            $config = $app['config']->get('acconplish.customsentinel.roles');

            return new IlluminateRoleRepository($config['model']);
        });
    }

    /**
     * Registers the checkpoints.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerCheckpoints()
    {
        $this->registerActivationCheckpoint();

        $this->registerThrottleCheckpoint();

        $this->app->singleton('customsentinel.checkpoints', function ($app) {
            $activeCheckpoints = $app['config']->get('acconplish.customsentinel.checkpoints');

            $checkpoints = [];

            foreach ($activeCheckpoints as $checkpoint) {
                if (! $app->offsetExists("customsentinel.checkpoint.{$checkpoint}")) {
                    throw new InvalidArgumentException("Invalid checkpoint [$checkpoint] given.");
                }

                $checkpoints[$checkpoint] = $app["customsentinel.checkpoint.{$checkpoint}"];
            }

            return $checkpoints;
        });
    }

    /**
     * Registers the activation checkpoint.
     *
     * @return void
     */
    protected function registerActivationCheckpoint()
    {
        $this->registerActivations();

        $this->app->singleton('customsentinel.checkpoint.activation', function ($app) {
            return new ActivationCheckpoint($app['customsentinel.activations']);
        });
    }

    /**
     * Registers the activations.
     *
     * @return void
     */
    protected function registerActivations()
    {
        $this->app->singleton('customsentinel.activations', function ($app) {
            $config = $app['config']->get('acconplish.customsentinel.activations');

            return new IlluminateActivationRepository($config['model'], $config['expires']);
        });
    }

    /**
     * Registers the throttle checkpoint.
     *
     * @return void
     */
    protected function registerThrottleCheckpoint()
    {
        $this->registerThrottling();

        $this->app->singleton('customsentinel.checkpoint.throttle', function ($app) {
            return new ThrottleCheckpoint(
                $app['customsentinel.throttling'], $app['request']->getClientIp()
            );
        });
    }

    /**
     * Registers the throttle.
     *
     * @return void
     */
    protected function registerThrottling()
    {
        $this->app->singleton('customsentinel.throttling', function ($app) {
            $model = $app['config']->get('acconplish.customsentinel.throttling.model');

            $throttling = $app['config']->get('acconplish.customsentinel.throttling');

            foreach ([ 'global', 'ip', 'user' ] as $type) {
                ${"{$type}Interval"} = $throttling[$type]['interval'];
                ${"{$type}Thresholds"} = $throttling[$type]['thresholds'];
            }

            return new IlluminateThrottleRepository(
                $model,
                $globalInterval,
                $globalThresholds,
                $ipInterval,
                $ipThresholds,
                $userInterval,
                $userThresholds
            );
        });
    }

    /**
     * Registers the reminders.
     *
     * @return void
     */
    protected function registerReminders()
    {
        $this->app->singleton('customsentinel.reminders', function ($app) {
            $config = $app['config']->get('acconplish.customsentinel.reminders');

            return new IlluminateReminderRepository(
                $app['customsentinel.users'], $config['model'], $config['expires']
            );
        });
    }

    /**
     * Registers customsentinel.
     *
     * @return void
     */
    protected function registerSentinel()
    {
        $this->app->singleton('customsentinel', function ($app) {
            $customsentinel = new Sentinel(
                $app['customsentinel.persistence'],
                $app['customsentinel.users'],
                $app['customsentinel.roles'],
                $app['customsentinel.activations'],
                $app['events']
            );

            if (isset($app['customsentinel.checkpoints'])) {
                foreach ($app['customsentinel.checkpoints'] as $key => $checkpoint) {
                    $customsentinel->addCheckpoint($key, $checkpoint);
                }
            }

            $customsentinel->setActivationRepository($app['customsentinel.activations']);
            $customsentinel->setReminderRepository($app['customsentinel.reminders']);

            $customsentinel->setRequestCredentials(function () use ($app) {
                $request = $app['request'];

                $login = $request->getUser();
                $password = $request->getPassword();

                if ($login === null && $password === null) {
                    return;
                }

                return compact('login', 'password');
            });

            $customsentinel->creatingBasicResponse(function () {
                $headers = ['WWW-Authenticate' => 'Basic'];

                return new Response('Invalid credentials.', 401, $headers);
            });

            return $customsentinel;
        });

        $this->app->alias('customsentinel', 'Acconplish\CustomSentinel\Sentinel');
    }

    /**
     * {@inheritDoc}
     */
    public function provides()
    {
        return [
            'customsentinel.session',
            'customsentinel.cookie',
            'customsentinel.persistence',
            'customsentinel.hasher',
            'customsentinel.users',
            'customsentinel.roles',
            'customsentinel.activations',
            'customsentinel.checkpoint.activation',
            'customsentinel.throttling',
            'customsentinel.checkpoint.throttle',
            'customsentinel.checkpoints',
            'customsentinel.reminders',
            'customsentinel',
        ];
    }

    /**
     * Garbage collect activations and reminders.
     *
     * @return void
     */
    protected function garbageCollect()
    {
        $config = $this->app['config']->get('acconplish.customsentinel');

        $this->sweep(
            $this->app['customsentinel.activations'], $config['activations']['lottery']
        );

        $this->sweep(
            $this->app['customsentinel.reminders'], $config['reminders']['lottery']
        );
    }

    /**
     * Sweep expired codes.
     *
     * @param  mixed  $repository
     * @param  array  $lottery
     * @return void
     */
    protected function sweep($repository, array $lottery)
    {
        if ($this->configHitsLottery($lottery)) {
            try {
                $repository->removeExpired();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Determine if the configuration odds hit the lottery.
     *
     * @param  array  $lottery
     * @return bool
     */
    protected function configHitsLottery(array $lottery)
    {
        return mt_rand(1, $lottery[1]) <= $lottery[0];
    }

    /**
     * Sets the user resolver on the request class.
     *
     * @return void
     */
    protected function setUserResolver()
    {
        $this->app->rebinding('request', function ($app, $request) {
            $request->setUserResolver(function () use ($app) {
                return $app['customsentinel']->getUser();
            });
        });
    }

    /**
     * Performs the necessary overrides.
     *
     * @return void
     */
    protected function setOverrides()
    {
        $config = $this->app['config']->get('acconplish.customsentinel');

        $users = $config['users']['model'];

        $roles = $config['roles']['model'];

        $persistences = $config['persistences']['model'];

        if (class_exists($users)) {
            if (method_exists($users, 'setRolesModel')) {
                forward_static_call_array([ $users, 'setRolesModel' ], [ $roles ]);
            }

            if (method_exists($users, 'setPersistencesModel')) {
                forward_static_call_array([ $users, 'setPersistencesModel' ], [ $persistences ]);
            }

            if (method_exists($users, 'setPermissionsClass')) {
                forward_static_call_array([ $users, 'setPermissionsClass' ], [ $config['permissions']['class'] ]);
            }
        }

        if (class_exists($roles) && method_exists($roles, 'setUsersModel')) {
            forward_static_call_array([ $roles, 'setUsersModel' ], [ $users ]);
        }

        if (class_exists($persistences) && method_exists($persistences, 'setUsersModel')) {
            forward_static_call_array([ $persistences, 'setUsersModel' ], [ $users ]);
        }
    }
}
