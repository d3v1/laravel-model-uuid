<?php

namespace Dyrynda\Database\Support;

/**
 * UUID generation trait.
 *
 * Include this trait in any Eloquent model where you wish to automatically set
 * a UUID field. When saving, if the UUID field has not been set, generate a
 * new UUID value, which will be set on the model and saved by Eloquent.
 *
 * @copyright 2017 Michael Dyrynda
 * @author    Michael Dyrynda <michael@dyrynda.com.au>
 * @license   MIT
 */
trait GeneratesUuid
{
    protected $uuidVersions = [
        'uuid1',
        'uuid3',
        'uuid4',
        'uuid5',
    ];

    /**
     * Boot the trait, adding a creating observer.
     *
     * When persisting a new model instance, we resolve the UUID field, then set
     * a fresh UUID, taking into account if we need to cast to binary or not.
     */
    public static function bootGeneratesUuid()
    {
        static::creating(function ($model) {
            $uuid = $model->resolveUuid();

            if (isset($model->attributes['uuid']) && ! is_null($model->attributes['uuid'])) {
                $uuid = $uuid->fromString(strtolower($model->attributes['uuid']));
            }

            $model->attributes['uuid'] = $model->hasCast('uuid') ? $uuid->getBytes() : $uuid->toString();
        });
    }


    /**
     * Resolve a UUID instance for the configured version.
     *
     * @return \Ramsey\Uuid\Uuid
     */
    public function resolveUuid()
    {
        return call_user_func("\Ramsey\Uuid\Uuid::{$this->resolveUuidVersion()}");
    }

    /**
     * Resolve the UUID version to use when setting the UUID value. Default to uuid4.
     *
     * @return string
     */
    public function resolveUuidVersion()
    {
        if (property_exists($this, 'uuidVersion') && in_array($this->uuidVersion, $this->uuidVersions)) {
            return $this->uuidVersion;
        }

        return 'uuid4';
    }

    /**
     * Scope queries to find by UUID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $uuid
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUuid($query, $uuid)
    {
        if ($this->hasCast('uuid')) {
            return $query->where(
                'uuid',
                call_user_func("\Ramsey\Uuid\Uuid::{$this->resolveUuidVersion()}")->fromString($uuid)->getBytes()
            );
        }

        return $query->where('uuid', $uuid);
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ($key == 'uuid' && ! is_null($value)) {
            return call_user_func("\Ramsey\Uuid\Uuid::{$this->resolveUuidVersion()}")->fromBytes($value)->toString();
        }

        return parent::castAttribute($key, $value);
    }
}
