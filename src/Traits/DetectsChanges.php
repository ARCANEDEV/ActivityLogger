<?php namespace Arcanedev\ActivityLogger\Traits;

use Illuminate\Database\Eloquent\Model;
use Arcanedev\ActivityLogger\Exceptions\CouldNotLogChanges;
use Illuminate\Support\Str;

/**
 * Trait     DetectsChanges
 *
 * @package  Arcanedev\ActivityLogger\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 *
 * @mixin \Arcanedev\ActivityLogger\Models\Activity
 */
trait DetectsChanges
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /**
     * The old attributes.
     *
     * @var array
     */
    protected $oldAttributes = [];

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    /**
     * Boot the trait.
     *
     * @return void
     */
    protected static function bootDetectsChanges()
    {
        if (static::eventsToBeRecorded()->contains('updated')) {
            static::updating(function ($model) {
                /** @var  static  $model */
                $oldValues = $model->replicate()->setRawAttributes($model->getOriginal());

                $model->oldAttributes = static::logChanges($oldValues);
            });
        }
    }

    /**
     * Get the logged attributes.
     *
     * @return array
     */
    public function attributesToBeLogged()
    {
        $attributes = [];

        if (isset(static::$logFillable) && static::$logFillable)
            $attributes = array_merge($attributes, $this->fillable);

        if (isset(static::$logAttributes) && is_array(static::$logAttributes))
            $attributes = in_array('*', static::$logAttributes)
                ? array_merge($attributes, array_keys($this->attributes), array_diff(static::$logAttributes, ['*']))
                : array_merge($attributes, static::$logAttributes);

        if (isset(static::$logAttributesToIgnore) && is_array(static::$logAttributesToIgnore))
            $attributes = array_diff($attributes, static::$logAttributesToIgnore);

        return $attributes;
    }

    /**
     * @return bool
     */
    public function shouldLogOnlyDirty()
    {
        return isset(static::$logOnlyDirty) ? static::$logOnlyDirty : false;
    }

    /**
     * Get the attribute values to be logged.
     *
     * @param  string  $event
     *
     * @return array
     */
    public function attributeValuesToBeLogged($event)
    {
        if ( ! count($this->attributesToBeLogged()))
            return [];

        $properties['attributes'] = static::logChanges(
            $this->exists ? ($this->fresh() ?? $this) : $this
        );

        if (static::eventsToBeRecorded()->contains('updated') && $event === 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);

            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }

        if ($this->shouldLogOnlyDirty() && isset($properties['old'])) {
            $properties['attributes'] = array_udiff_assoc(
                $properties['attributes'],
                $properties['old'],
                function ($new, $old) { return $new <=> $old; }
            );
            $properties['old'] = collect($properties['old'])
                ->only(array_keys($properties['attributes']))
                ->all();
        }

        return $properties;
    }

    /**
     * Log the changes.
     *
     * @param  \Illuminate\Database\Eloquent\Model|mixed  $model
     *
     * @return array
     */
    public static function logChanges($model)
    {
        $changes = [];

        foreach ($model->attributesToBeLogged() as $attribute) {
            $changes += Str::contains($attribute, '.')
                ? self::getRelatedModelAttributeValue($model, $attribute)
                : collect($model)->only($attribute)->toArray();
        }

        return $changes;
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Get the attribute value for the related model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string                               $attribute
     *
     * @return array
     */
    protected static function getRelatedModelAttributeValue($model, $attribute)
    {
        if (substr_count($attribute, '.') > 1)
            throw CouldNotLogChanges::make(
                "Cannot log attribute `{$attribute}`. Can only log attributes of a model or a directly related model."
            );

        list($relatedModelName, $relatedAttribute) = explode('.', $attribute);

        $relatedModel = $model->$relatedModelName ?? $model->$relatedModelName();

        return [
            "{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute ?? null
        ];
    }
}
