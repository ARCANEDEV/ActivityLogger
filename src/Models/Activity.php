<?php namespace Arcanedev\ActivityLogger\Models;

use Illuminate\Database\Eloquent\Builder;
use Arcanedev\Support\Database\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Class     Activity
 *
 * @package  Arcanedev\ActivityLogger\Exceptions
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @property  int                             id
 * @property  string                          log_name
 * @property  string                          log_level
 * @property  string                          description
 * @property  int                             subject_id
 * @property  string                          subject_type
 * @property  int                             causer_id
 * @property  string                          causer_type
 * @property  \Illuminate\Support\Collection  properties
 * @property  \Carbon\Carbon                  created_at
 * @property  \Carbon\Carbon                  updated_at
 *
 * @property  mixed                           causer
 * @property  mixed                           subject
 *
 * @method  static  \Illuminate\Database\Eloquent\Builder  inLog(...$logNames)
 * @method  static  \Illuminate\Database\Eloquent\Builder  causedBy($causer)
 * @method  static  \Illuminate\Database\Eloquent\Builder  forSubject($causer)
 */
class Activity extends Model
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $fillable = [
        'log_name',
        'log_level',
        'description',
        'properties',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'         => 'integer',
        'subject_id' => 'integer',
        'causer_id'  => 'integer',
        'properties' => 'collection',
    ];

    /* -----------------------------------------------------------------
     |  Constructor
     | -----------------------------------------------------------------
     */

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setConnection(config('activity-logger.database.connection'));
        $this->setPrefix(config('activity-logger.database.prefix'));
        $this->setTable(config('activity-logger.activities.table'));

        parent::__construct($attributes);
    }

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    /**
     * Subject's relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject()
    {
        return $this->morphTo()
            ->when(config('activity-logger.subjects.soft-deleted'), function (Builder $query) {
                return $query->withTrashed();
            });
    }

    /**
     * Causer's relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function causer()
    {
        return $this->morphTo()
            ->when(config('activity-logger.causers.soft-deleted'), function (Builder $query) {
                return $query->withTrashed();
            });
    }

    /* -----------------------------------------------------------------
     |  Scopes
     | -----------------------------------------------------------------
     */

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  ...$logNames
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLog(Builder $query, ...$logNames)
    {
        return $query->whereIn(
            'log_name',
            is_array($logNames[0]) ? $logNames[0] : $logNames
        );
    }

    /**
     * Scope a query to only include activities by a given causer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model    $causer
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCausedBy(Builder $query, $causer)
    {
        return $query->where([
            'causer_type' => $causer->getMorphClass(),
            'causer_id'   => $causer->getKey()
        ]);
    }

    /**
     * Scope a query to only include activities for a given subject.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model    $subject
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject(Builder $query, $subject)
    {
        return $query->where([
            'subject_type' => $subject->getMorphClass(),
            'subject_id'   => $subject->getKey(),
        ]);
    }

    /* -----------------------------------------------------------------
     |  Getters & Setters
     | -----------------------------------------------------------------
     */

    /**
     * Get the extra properties with the given name.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function getExtraProperty($key)
    {
        return Arr::get($this->properties->toArray(), $key);
    }

    /**
     * Get the changes.
     *
     * @return \Illuminate\Support\Collection
     */
    public function changes()
    {
        if ( ! $this->properties instanceof Collection)
            return new Collection;

        $changes = array_filter($this->properties->toArray(), function ($key) {
            return in_array($key, ['attributes', 'old']);
        }, ARRAY_FILTER_USE_KEY);

        return new Collection($changes);
    }
}
