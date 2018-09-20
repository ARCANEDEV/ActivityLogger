<?php namespace Arcanedev\ActivityLogger\Exceptions;

use Arcanedev\ActivityLogger\Models\Activity;

/**
 * Class     InvalidConfiguration
 *
 * @package  Arcanedev\ActivityLogger\Exceptions
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class InvalidConfiguration extends ActivityLoggerException
{
    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public static function modelIsNotValid($className)
    {
        return static::make("The given model class `$className` does not extend `".Activity::class.'`');
    }
}
