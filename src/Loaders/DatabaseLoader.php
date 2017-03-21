<?php

namespace MikeZange\LaravelDatabaseTranslation\Loaders;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\LoaderInterface;
use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;

/**
 * Class DatabaseLoader.
 */
class DatabaseLoader implements LoaderInterface
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default path for the loader.
     *
     * @var string
     */
    protected $path;

    /**
     * The cache repository.
     *
     * @var CacheRepository
     */
    protected $cache;

    /**
     * All of the namespace hints.
     *
     * @var array
     */
    protected $hints = [];

    /**
     * The cache repository.
     *
     * @var TranslationRepository
     */
    protected $translationRepository;

    /**
     * The cache repository.
     *
     * @var FileLoader
     */
    protected $laravelFileLoader;

    /**
     * DatabaseLoader constructor.
     *
     * @param Filesystem $filesystem
     * @param $path
     * @param TranslationRepository $translationRepository
     * @param CacheRepository       $cache
     */
    public function __construct(
        Filesystem $filesystem,
        $path,
        TranslationRepository $translationRepository,
        CacheRepository $cache
    ) {
        $this->files = $filesystem;
        $this->path = $path;
        $this->translationRepository = $translationRepository;
        $this->cache = $cache;
        $this->laravelFileLoader = new FileLoader($this->files, $this->path);
    }

    /**
     * Load the messages for the given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $cacheKey = "{$locale}.{$namespace}.{$group}";
        $cacheTags = $this->cache->tags(['translations']);

        return $cacheTags->remember($cacheKey, 60, function () use ($locale, $namespace, $group) {
            return $this->loadCombinedTranslations($locale, $group, $namespace);
        });
    }

    /**
     * Get the messages for the given locale from the database and merge
     * them with the file based ones as a fallback for a given locale.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    protected function loadCombinedTranslations($locale, $group, $namespace)
    {
        return array_replace_recursive(
            $this->laravelFileLoader->load($locale, $group, $namespace),
            $this->loadFromDatabase($locale, $group, $namespace)
        );
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param string $namespace
     * @param string $hint
     *
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
        $this->laravelFileLoader->addNamespace($namespace, $hint);
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }

    /**
     * Load the required translations from the database.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    protected function loadFromDatabase($locale, $group, $namespace)
    {
        if (is_null($namespace) || $namespace == '*') {
            return $this->loadGroup($locale, $group);
        }

        return $this->loadNamespaced($locale, $namespace, $group);
    }

    /**
     * Load a non-namespaced translation group.
     *
     * @param string $locale
     * @param string $group
     *
     * @return array
     */
    protected function loadGroup($locale, $group)
    {
        $translations = $this->translationRepository->getItems(null, $group);

        if ($translations) {
            return $this->createFormattedArray($translations, $locale);
        }

        return [];
    }

    /**
     * Load a namespaced translation group.
     *
     * @param string $locale
     * @param string $group
     * @param string $namespace
     *
     * @return array
     */
    protected function loadNamespaced($locale, $namespace, $group)
    {
        $translations = $this->translationRepository->getItems($namespace, $group);

        if ($translations) {
            return $this->createFormattedArray($translations, $locale);
        }

        return [];
    }

    /**
     * Create a formatted array as if it was coming from the default loader.
     *
     * @param Collection $translations
     * @param string     $locale
     *
     * @return array
     */
    protected function createFormattedArray($translations, $locale)
    {
        $array = [];

        foreach ($translations as $translation) {
            $values = collect($translation->values)->toArray();
            $value = $this->getValueForLocale($values, $locale);
            if ($value) {
                array_set($array, $translation->key, $value);
            }
        }

        return $array;
    }

    /**
     * Grab the value of the translation for the selected locale
     * and then attempt the fallback locale.
     *
     * @param string     $locale
     * @param Collection $values
     *
     * @return string|bool
     */
    protected function getValueForLocale($values, $locale)
    {
        if (!$this->checkLocaleExists($values, $locale)) {
            return false;
        }

        if (!$this->checkLocaleExists($values, config('app.fallback_locale'))) {
            return false;
        }

        return $values[$locale];
    }

    /**
     * Check to see if the locale is contained in the translation json,
     * if not check for fallback.
     *
     * @param Collection $values
     * @param string     $locale
     *
     * @return bool
     */
    protected function checkLocaleExists($values, $locale)
    {
        if (array_has($values, $locale)) {
            return true;
        }

        return false;
    }

    /**
     * Get the loaded Laravel File loader.
     *
     * @return FileLoader
     */
    public function getFileLoader()
    {
        return $this->laravelFileLoader;
    }
}
