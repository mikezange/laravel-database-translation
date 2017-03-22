<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation database table name
    |--------------------------------------------------------------------------
    |
    | Name of the table in the database where your translations will be stored
    |
    */
    'table' => 'translations',

    /*
    |--------------------------------------------------------------------------
    | Translation Model
    |--------------------------------------------------------------------------
    |
    | If you need to overwrite or extend functionality of the model extend
    | this class and replace the line below with your new one.
    |
    | e.g. 'model' => \App\Models\NewTranslationModel::class,
    |
    */
    'model' => \MikeZange\LaravelDatabaseTranslation\Models\Translation::class,

    /*
    |--------------------------------------------------------------------------
    | Enable caching of translations
    |--------------------------------------------------------------------------
    |
    | Available options true / false
    |
    */
    'cache_enabled' => true,
];
