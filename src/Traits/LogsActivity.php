<?php namespace Arcanedev\ActivityLogger\Traits;

use Arcanedev\ActivityLogger\ActivityLogger;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Trait     LogsActivity
 *
 * @package  Arcanedev\ActivityLogger\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @property  \Illuminate\Database\Eloquent\Collection  activities
 */
trait LogsActivity
{
    /* -----------------------------------------------------------------
     |  Traits
     | -----------------------------------------------------------------
     */

    use DetectsChanges;

    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var  bool */
    protected $enableLoggingModelsEvents = true;

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    /**
     * Activities' relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activities()
    {
        return $this->morphMany(ActivityLogger::getActivityModel(), 'subject');
    }

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    protected static function bootLogsActivity()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            return static::$eventName(function ($model) use ($eventName) {
                /** @var  static  $model */
                if ( ! $model->shouldLogEvent($eventName))
                    return;

                $description = $model->getDescriptionForEvent($eventName);

                if ($description === '')
                    return;

                activity($model->getLogNameToUse($eventName))
                    ->performedOn($model)
                    ->withProperties($model->attributeValuesToBeLogged($eventName))
                    ->track($description, $model->getLogLevelToUse($eventName));
            });
        });
    }

    /**
     * Disable the logging.
     *
     * @return static
     */
    public function disableLogging()
    {
        $this->enableLoggingModelsEvents = false;

        return $this;
    }

    /**
     * Enable the logging.
     *
     * @return static
     */
    public function enableLogging()
    {
        $this->enableLoggingModelsEvents = true;

        return $this;
    }

    /**
     * Get the description for event.
     *
     * @param  string  $eventName
     *
     * @return string
     */
    public function getDescriptionForEvent($eventName)
    {
        return $eventName;
    }

    /**
     * Get the log name to use.
     *
     * @param  string  $eventName
     *
     * @return string
     */
    public function getLogNameToUse($eventName)
    {
        return isset(static::$logName)
            ? static::$logName
            : config('activity-logger.defaults.log-name', $eventName);
    }

    /**
     * Get the log level to use.
     *
     * @param  string  $eventName
     *
     * @return string
     */
    public function getLogLevelToUse($eventName = '')
    {
        return isset(static::$logLevel)
            ? static::$logLevel
            : config('activity-logger.defaults.log-level');
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Get the events to be recorded.
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function eventsToBeRecorded()
    {
        if (isset(static::$recordEvents))
            return collect(static::$recordEvents);

        $events = collect(['created', 'updated', 'deleted']);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class)))
            $events->push('restored');

        return $events;
    }

    /**
     * Get the attributes that should be ignored.
     *
     * @return array
     */
    public function ignoredAttributesFromLogging()
    {
        return [];
    }

    /**
     * @param  string  $eventName
     *
     * @return bool
     */
    protected function shouldLogEvent($eventName)
    {
        if ( ! $this->enableLoggingModelsEvents)
            return false;

        if ( ! in_array($eventName, ['created', 'updated']))
            return true;

        if (Arr::has($this->getDirty(), 'deleted_at') && $this->getDirty()['deleted_at'] === null)
            return false;

        return count(Arr::except($this->getDirty(), $this->ignoredAttributesFromLogging())) > 0;
    }
}
