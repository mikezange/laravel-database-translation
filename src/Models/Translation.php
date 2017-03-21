<?php

namespace MikeZange\LaravelDatabaseTranslation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Translation.
 */
class Translation extends Model
{
    protected $table;

    /**
     *  List of variables that can be mass assigned.
     *
     *  @var array
     */
    protected $fillable = ['namespace', 'group', 'key', 'values'];

    /**
     * Translation constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('database.translations.table');
        parent::__construct($attributes);
    }

    /**
     * @param $value
     *
     * @return array
     */
    public function getValuesAttribute($value)
    {
        return (array) json_decode($value);
    }

    /**
     * @param $value
     */
    public function setValuesAttribute($value)
    {
        $this->attributes['values'] = json_encode($value);
    }
}
