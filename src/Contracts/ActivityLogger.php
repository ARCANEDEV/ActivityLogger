<?php namespace Arcanedev\ActivityLogger\Contracts;

use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;

/**
 * Interface     ActivityLogger
 *
 * @package  Arcanedev\ActivityLogger\Contracts
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
interface ActivityLogger extends LoggerInterface
{
    /* -----------------------------------------------------------------
     |  Getters & Setters
     | -----------------------------------------------------------------
     */

    /**
     * Set the log name (alias).
     *
     * @param  string  $logName
     *
     * @return static
     */
    public function inLog($logName);

    /**
     * Set the log name.
     *
     * @param  string  $logName
     *
     * @return static
     */
    public function useLog($logName);

    /**
     * Set the performed resource/model (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return static
     */
    public function on(Model $model);

    /**
     * Set the performed resource/model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return static
     */
    public function performedOn(Model $model);

    /**
     * Set the caused by (alias).
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $causedBy
     *
     * @return static
     */
    public function by($causedBy);

    /**
     * Set the caused by.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $causedBy
     *
     * @return static
     */
    public function causedBy($causedBy);

    /**
     * Set the properties.
     *
     * @param  array|\Illuminate\Support\Collection  $properties
     *
     * @return static
     */
    public function withProperties($properties);

    /**
     * Set a property.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return static
     */
    public function withProperty($key, $value);

    /**
     * Enable the activity logger.
     *
     * @return static
     */
    public function enable();

    /**
     * Disable the activity logger.
     *
     * @return static
     */
    public function disable();

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    /**
     * Log an activity.
     *
     * @param  string  $description
     * @param  string  $level
     * @param  array   $context
     *
     * @return \Arcanedev\ActivityLogger\Models\Activity|mixed
     */
    public function track($description, $level = null, array $context = []);

    /* -----------------------------------------------------------------
     |  Check Methods
     | -----------------------------------------------------------------
     */

    /**
     * Check if the activity logger is enabled.
     *
     * @return bool
     */
    public function enabled();

    /**
     * Check if the activity logger is disabled.
     *
     * @return bool
     */
    public function disabled();

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Get the activity model.
     *
     * @return \Arcanedev\ActivityLogger\Models\Activity|mixed
     */
    public static function newActivity();

    /**
     * Get the activity model class.
     *
     * @return string
     */
    public static function getActivityModel();
}
