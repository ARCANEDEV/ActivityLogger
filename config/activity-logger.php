<?php

return [

    /* -----------------------------------------------------------------
     |  Enable
     | -----------------------------------------------------------------
     */

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /* -----------------------------------------------------------------
     |  Database
     | -----------------------------------------------------------------
     */

    'database' => [
        'connection' => env('DB_CONNECTION'),
        'prefix'     => null,
    ],

    /* -----------------------------------------------------------------
     |  Defaults
     | -----------------------------------------------------------------
     */

    'defaults' => [
        // If no log name is passed to the activity() helper we use this default log name.
        'log-name'  => 'default',

        'log-level' => Psr\Log\LogLevel::INFO,

        /*
         * You can specify an auth driver here that gets user models.
         * If this is null we'll use the default Laravel auth driver.
         */
        'auth-driver' => null,
    ],

    /* -----------------------------------------------------------------
     |  Models
     | -----------------------------------------------------------------
     */

    'activities' => [
        'model' => Arcanedev\ActivityLogger\Models\Activity::class,
        'table' => 'activity_logs'
    ],

    'subjects'  => [
        // If set to true, the subject returns soft deleted models.
        'soft-deleted' => false,
    ],

    'causers'  => [
        // If set to true, the causer returns soft deleted models.
        'soft-deleted' => false,
    ],

    /* -----------------------------------------------------------------
     |  Cleaning
     | -----------------------------------------------------------------
     */

    /*
     * When the `clean-command` is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */

    'delete-records-older-than-days' => 365,

];
