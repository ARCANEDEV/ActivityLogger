<?php

use Arcanedev\ActivityLogger\Contracts\ActivityLogger;

if ( ! function_exists('activity')) {
    /**
     * Get the activity logger.
     *
     * @param  string|null  $logName
     *
     * @return \Arcanedev\ActivityLogger\Contracts\ActivityLogger
     */
    function activity(string $logName = null)
    {
        return app(ActivityLogger::class)->useLog(
            $logName ?? config('activity-logger.defaults.log-name')
        );
    }
}
