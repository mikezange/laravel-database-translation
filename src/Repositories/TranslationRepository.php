<?php

namespace MikeZange\LaravelDatabaseTranslation\Repositories;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Application;
use MikeZange\LaravelDatabaseTranslation\Models\Translation;

/**
 * Class TranslationRepository.
 */
class TranslationRepository
{
    protected $model;
    protected $app;
    protected $cache;
    protected $errors = null;

    /**
     *  Constructor.
     *
     * @param Application $app
     * @param CacheRepository $cache
     */
    public function __construct(Application $app, CacheRepository $cache)
    {
        $this->model = $app->make(config('database.translations.model'));
        $this->app = $app;
        $this->cache = $cache;
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
     *  Check if the model's table exists.
     *
     *  @return bool
     */
    public function tableExists()
    {
        return $this->model->getConnection()->getSchemaBuilder()->hasTable($this->model->getTable());
    }

    /**
     *  Retrieve all entries.
     *
     *  @param array $related Related object to include.
     *  @param int $perPage Number of records to retrieve per page. If zero the whole result set is returned.
     *
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
     *  @param int $id
     *
     *  @return \Illuminate\Database\Eloquent\Model
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     *  Remove an entry.
     *
     *  @param  string $id
     *
     *  @return bool
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
     *  @return int
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     *  Return all items for a given namespace and group.
     *
     *  @param  string $namespace
     *  @param  string $group
     *
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
     *  Return a specific item with its translation for a given namespace, group and key.
     *
     *  @param  string $namespace
     *  @param  string $group
     *  @param  string $key
     *
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
     *  If the attributes are not valid.
     *
     *  @param  array $attributes
     *
     *  @return bool
     **/
    public function create(array $attributes)
    {
        return $this->validate($attributes) ? $this->model->create($attributes) : null;
    }

    /**
     *  Update the translations of an existing key and locale by id.
     *
     *  @param int $id
     *  @param string $locale
     *  @param string $value
     *  @param bool $overwrite
     *
     *  @return bool
     **/
    public function updateById($id, $locale, $value, $overwrite = true) : bool
    {
        $line = $this->model->find($id);
        return $this->updateTranslation($line, $locale, $value, $overwrite);
    }

    /**
     *  Update the translations of an existing key and locale.
     *
     *  @param  Translation $line
     *  @param  string $locale
     *  @param  string $value
     *  @param  bool $overwrite
     *
     *  @return bool
     **/
    public function updateTranslation(Translation $line, $locale, $value, $overwrite = true) : bool
    {
        if (empty($line->getTranslation('values', $locale)) || $overwrite) {
            $line->setTranslation('values', $locale, $value);
        }

        return $this->save($line);
    }

    /**
     * @param Translation $translation
     * @return bool
     */
    public function save(Translation $translation) : bool
    {
        return $translation->save();
    }

    /**
     *  Validate the given attributes.
     *
     *  @param  array    $attributes
     *
     *  @return bool
     */
    public function validate(array $attributes) : bool
    {
        $table = $this->model->getTable();
        $namespace = array_get($attributes, 'namespace');
        $group = array_get($attributes, 'group');

        $rules = [
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
