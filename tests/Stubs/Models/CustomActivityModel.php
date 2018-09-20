<?php namespace Arcanedev\ActivityLogger\Tests\Stubs\Models;

use Arcanedev\ActivityLogger\Models\Activity;

/**
 * Class     CustomActivityModel
 *
 * @package  Arcanedev\ActivityLogger\Tests\Models
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @property \Illuminate\Support\Collection  custom_properties
 */
class CustomActivityModel extends Activity
{
    /**
     * Get the `custom_properties` attribute.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCustomPropertiesAttribute()
    {
        return $this->changes();
    }
}
