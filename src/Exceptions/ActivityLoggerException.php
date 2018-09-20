<?php namespace Arcanedev\ActivityLogger\Exceptions;

use Throwable;

/**
 * Class     ActivityLoggerException
 *
 * @package  Arcanedev\ActivityLogger\Exceptions
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class ActivityLoggerException extends \Exception
{
    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public static function make($message = '', $code = 0, Throwable $previous = null)
    {
        return new static($message, $code, $previous);
    }
}
