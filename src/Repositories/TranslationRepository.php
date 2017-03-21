<?php namespace MikeZange\LaravelDatabaseTranslation\Repositories;

use Illuminate\Foundation\Application;
use MikeZange\LaravelDatabaseTranslation\Models\Translation;

/**
 * Class TranslationRepository
 * @package MikeZange\LaravelDatabaseTranslation\Repositories
 */
class TranslationRepository
{
    protected $model;
    protected $app;
    protected $errors = null;
    /**
     *  Constructor
     *  @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->model = $app->make(config('database.translations.model'));
        $this->app = $app;
    }

    /**
     *  Return the model related to this finder.
     *
     *  @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     *  Check if the model's table exists
     *
     *  @return boolean
     */
    public function tableExists()
    {
        return $this->model->getConnection()->getSchemaBuilder()->hasTable($this->model->getTable());
    }

    /**
     *  Retrieve all entries.
     *
     *  @param array $related Related object to include.
     *  @param integer $perPage Number of records to retrieve per page. If zero the whole result set is returned.
     *  @return \Illuminate\Database\Eloquent\Model
     */
    public function all($related = [], $perPage = 0)
    {
        $results = $this->model->with($related)->orderBy('created_at', 'DESC');

        return $perPage ? $results->paginate($perPage) : $results->get();
    }

    /**
     *  Retrieve a single entry by id.
     *
     *  @param integer $id
     *  @return \Illuminate\Database\Eloquent\Model
     */
    public function find($id, $related = [])
    {
        return $this->model->with($related)->find($id);
    }

    /**
     *  Remove an entry.
     *
     *  @param  string $id
     *  @return boolean
     */
    public function delete($id)
    {
        $model = $this->model->where('id', $id)->first();
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    /**
     *  Returns total number of entries in DB.
     *
     *  @return integer
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     *  Return all items for a given namespace and group
     *
     *  @param  string $namespace
     *  @param  string $group
     *  @return \Illuminate\Database\Eloquent\Collection
     */
    public function getItems($namespace, $group)
    {
        return $this->model
             ->where('namespace', $namespace)
             ->where('group', $group)
             ->get();
    }

    /**
     *  Return a specific item with its translation for a given namespace, group and key
     *
     *  @param  string $namespace
     *  @param  string $group
     *  @param  string $key
     *  @return \Illuminate\Database\Eloquent\Builder
     */
    public function getItem($namespace, $group, $key)
    {
        return $this->model
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('key', $key)
            ->first();
    }

    /**
     *  Insert a new translation into the database.
     *  If the attributes are not valid
     *
     *  @param  array $attributes
     *  @return boolean
     **/

    public function create(array $attributes)
    {
        return $this->validate($attributes) ? $this->model->create($attributes) : null;
    }

    /**
     *  Update the translations of an existing key and locale by id
     *
     *  @param integer $id
     *  @param string $locale
     *  @param string $value
     *  @param bool $overwrite
     *  @return boolean
     **/
    public function updateById($id, $locale, $value, $overwrite = true)
    {
        $line = $this->model->find($id);
        $this->updateTranslations($line, $locale, $value, $overwrite = true);
    }

    /**
     *  Update the translations of an existing key and locale
     *
     *  @param  Translation $line
     *  @param  string $locale
     *  @param  string $value
     *  @param  bool $overwrite
     *  @return boolean
     **/
    public function updateTranslations(Translation $line, $locale, $value, $overwrite = true)
    {
        $translations = $line->values;

        if ($overwrite) {
            $translations[$locale] = $value;
        } else {
            if (!array_has($translations, $locale)) {
                $translations = array_merge($translations, ["{$locale}" => $value]);
            }
        }

        $line->values = $translations;

        return $this->save($line);
    }

    /**
     * @param Translation $translation
     */
    public function save(Translation $translation)
    {
        $translation->save();
    }

    /**
     *  Validate the given attributes
     *
     *  @param  array    $attributes
     *  @return boolean
     */
    public function validate(array $attributes)
    {
        $table     = $this->model->getTable();
        $namespace = array_get($attributes, 'namespace');
        $group     = array_get($attributes, 'group');

        $rules     = [
            'group'     => 'required',
            'key'       => "required|unique:{$table},key,NULL,id,namespace,{$namespace},group,{$group}",
        ];

        $validator = $this->app['validator']->make($attributes, $rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        }

        return true;
    }
}
