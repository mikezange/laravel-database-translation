<?php

namespace MikeZange\LaravelDatabaseTranslation\Models;

use Illuminate\Database\Eloquent\Model;
use MikeZange\LaravelDatabaseTranslation\Translatable;

/**
 * Class Translation.
 */
class Translation extends Model
{
    use Translatable;

    protected $table;

    /**
     *  List of variables that can be mass assigned.
     *
     *  @var array
     */
    protected $fillable = ['namespace', 'group', 'key', 'values'];

    public $translatable = ['values'];

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
}
