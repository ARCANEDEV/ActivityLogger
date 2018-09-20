<?php namespace Arcanedev\ActivityLogger;

use Arcanedev\ActivityLogger\Exceptions\CouldNotLogActivity;
use Arcanedev\ActivityLogger\Helpers\Placeholder;
use Arcanedev\ActivityLogger\Models\Activity;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

/**
 * Class     ActivityLogger
 *
 * @package  Arcanedev\ActivityLogger
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class ActivityLogger implements Contracts\ActivityLogger
{
    /* -----------------------------------------------------------------
     |  Traits
     | -----------------------------------------------------------------
     */

    use Concerns\Logger,
        Macroable;

    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var \Illuminate\Contracts\Auth\Factory */
    protected $auth;

    /** @var \Illuminate\Contracts\Config\Repository */
    private static $config;

    protected $logName = '';

    /** @var  string */
    protected $logLevel;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $performedOn;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $causedBy;

    /** @var \Illuminate\Support\Collection */
    protected $properties;

    /* -----------------------------------------------------------------
     |  Constructor
     | -----------------------------------------------------------------
     */

    public function __construct(Repository $config, AuthFactory $auth)
    {
        static::$config   = $config;
        $this->auth       = $auth;
        $this->properties = new Collection;
        $this->causedBy   = $auth->guard($this->getAuthDriver())->user();
        $this->logName    = static::$config->get('activity-logger.defaults.log-name');
        $this->logLevel   = static::$config->get('activity-logger.defaults.log-level');
    }

    /* -----------------------------------------------------------------
     |  Getters & Setters
     | -----------------------------------------------------------------
     */

    /**
     * Get the auth driver.
     *
     * @return mixed
     */
    protected function getAuthDriver()
    {
        return static::$config->get('activity-logger.defaults.auth-driver', $this->auth->getDefaultDriver());
    }

    /**
     * Set the log name (alias).
     *
     * @param  string  $logName
     *
     * @return static
     */
    public function inLog($logName)
    {
        return $this->useLog($logName);
    }

    /**
     * Set the log name.
     *
     * @param  string  $logName
     *
     * @return static
     */
    public function useLog($logName)
    {
        $this->logName = $logName;

        return $this;
    }

    /**
     * Set the performed resource/model (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return static
     */
    public function on(Model $model)
    {
        return $this->performedOn($model);
    }

    /**
     * Set the performed resource/model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return static
     */
    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    /**
     * Set the caused by (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $causedBy
     *
     * @return static
     */
    public function by($causedBy)
    {
        return $this->causedBy($causedBy);
    }

    /**
     * Set the caused by.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $causedBy
     *
     * @return static
     */
    public function causedBy($causedBy)
    {
        if ($causedBy !== null)
            $this->causedBy = $this->normalizeCauser($causedBy);

        return $this;
    }

    /**
     * Set the properties.
     *
     * @param  array|\Illuminate\Support\Collection  $properties
     *
     * @return static
     */
    public function withProperties($properties)
    {
        $this->properties = new Collection($properties);

        return $this;
    }

    /**
     * Set a property.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return static
     */
    public function withProperty($key, $value)
    {
        $this->properties->put($key, $value);

        return $this;
    }

    /**
     * Enable the activity logger.
     *
     * @return static
     */
    public function enable()
    {
        static::$config->set('activity-logger.enabled', true);

        return $this;
    }

    /**
     * Disable the activity logger.
     *
     * @return static
     */
    public function disable()
    {
        static::$config->set('activity-logger.enabled', false);

        return $this;
    }

    /**
     * Log an activity (alias).
     *
     * @param  string  $description
     * @param  string  $level
     * @param  array   $context
     *
     * @return \Arcanedev\ActivityLogger\Models\Activity|mixed
     */
    public function track($description, $level = null, array $context = [])
    {
        if ($this->disabled())
            return null;

        return tap(static::newActivity(), function (Activity $activity) use ($level, $description, $context) {
            if ($this->performedOn)
                $activity->subject()->associate($this->performedOn);

            if ($this->causedBy)
                $activity->causer()->associate($this->causedBy);

            $activity->log_name    = $this->logName;
            $activity->log_level   = $level ?: $this->logLevel;
            $activity->properties  = $this->properties->merge($context);
            $activity->description = Placeholder::replace($description, $activity);

            $activity->save();
        });
    }

    /* -----------------------------------------------------------------
     |  Check Methods
     | -----------------------------------------------------------------
     */

    /**
     * Check if the activity logger is enabled.
     *
     * @return bool
     */
    public function enabled()
    {
        $enabled = static::$config->get('activity-logger.enabled') ?? true;

        return $enabled === true;
    }

    /**
     * Check if the activity logger is disabled.
     *
     * @return bool
     */
    public function disabled()
    {
        return ! $this->enabled();
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Normalise the causer.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $causer
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function normalizeCauser($causer)
    {
        if ($causer instanceof Model)
            return $causer;

        $model = $this->auth->guard($this->getAuthDriver())
            ->getProvider()
            ->retrieveById($causer);

        if (is_null($model))
            throw CouldNotLogActivity::make("Could not determine a user with identifier `{$causer}`.");

        return $model;
    }

    /**
     * Get the activity model.
     *
     * @return \Arcanedev\ActivityLogger\Models\Activity|mixed
     */
    public static function newActivity()
    {
        return app()->make(static::getActivityModel());
    }

    /**
     * Get the activity model class.
     *
     * @return string
     */
    public static function getActivityModel()
    {
        $model = static::$config->get('activity-logger.activities.model') ?? Activity::class;

        if ( ! is_a($model, Activity::class, true))
            throw Exceptions\InvalidConfiguration::modelIsNotValid($model);

        return $model;
    }
}
