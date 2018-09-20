<?php namespace Arcanedev\ActivityLogger\Traits;

use Arcanedev\ActivityLogger\ActivityLogger;

/**
 * Trait     HasActivity
 *
 * @package  Arcanedev\ActivityLogger\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @mixin \Arcanedev\ActivityLogger\Models\Activity
 */
trait HasActivity
{
    /* -----------------------------------------------------------------
     |  Traits
     | -----------------------------------------------------------------
     */

    use LogsActivity;

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    /**
     * Actions' relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function actions()
    {
        return $this->morphMany(ActivityLogger::getActivityModel(), 'causer');
    }
}
