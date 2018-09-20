<?php namespace Arcanedev\ActivityLogger\Traits;

/**
 * Trait     CausesActivity
 *
 * @package  Arcanedev\ActivityLogger\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @property  \Illuminate\Database\Eloquent\Collection  activities
 */
trait CausesActivity
{
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
        return $this->morphMany(activity()->getActivityModel(), 'causer');
    }
}
