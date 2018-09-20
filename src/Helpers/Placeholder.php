<?php namespace Arcanedev\ActivityLogger\Helpers;

use Arcanedev\ActivityLogger\Models\Activity;
use Illuminate\Support\Arr;

/**
 * Class     Placeholder
 *
 * @package  Arcanedev\ActivityLogger\Helpers
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class Placeholder
{
    /* -----------------------------------------------------------------
     |  Constants
     | -----------------------------------------------------------------
     */

    const PATTERN = '/:[a-z0-9._-]+/i';

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public static function replace(string $description, Activity $activity)
    {
        return preg_replace_callback(static::PATTERN, function ($match) use ($activity) {
            $match     = $match[0];
            $attribute = static::strBetween($match, ':', '.');

            if ( ! static::hasMatch($attribute))
                return $match;

            return is_null($attributeValue = $activity->$attribute)
                ? $match
                : Arr::get($attributeValue->toArray(), static::getPropertyName($match), $match);
        }, $description);
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    private static function hasMatch($attribute)
    {
        return in_array($attribute, ['subject', 'causer', 'properties']);
    }

    /**
     * @param  string  $string
     * @param  string  $start
     * @param  string  $end
     *
     * @return string
     */
    private static function strBetween($string, $start, $end)
    {
        $stringWithoutStart = explode($start, $string)[1];

        return explode($end, $stringWithoutStart)[0];
    }

    /**
     * Get the property name.
     *
     * @param  string  $match
     *
     * @return string
     */
    private static function getPropertyName($match)
    {
        return substr($match, strpos($match, '.') + 1);
    }
}
