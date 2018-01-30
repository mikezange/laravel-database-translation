<?php

namespace MikeZange\LaravelDatabaseTranslation\Loaders;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Translation\FileLoader;
use Illuminate\Contracts\Translation\Loader;
use MikeZange\LaravelDatabaseTranslation\Models\Translation;
use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;

/**
 * Class DatabaseLoader.
 */
class DatabaseLoader implements Loader
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
     * Is cache enabled.
     *
     * @var bool
     */
    protected $cacheEnabled;

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

        $this->laravelFileLoader = new FileLoader($this->files, $this->path);

        $this->cache = $cache;
        $this->cacheEnabled = config('database.translations.cache_enabled');
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
    public function load($locale, $group, $namespace = null) : array
    {
        $cacheKey = "translations.{$locale}.{$namespace}.{$group}";

        if (!$this->cacheEnabled) {
            $translations = $this->loadCombinedTranslations($locale, $group, $namespace);
        } else {
            $translations = $this->cache->remember($cacheKey, 1440, function () use ($locale, $namespace, $group) {
                return $this->loadCombinedTranslations($locale, $group, $namespace);
            });
        }

        return $translations;
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
    protected function loadCombinedTranslations($locale, $group, $namespace) : array
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
    public function namespaces() : array
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
    protected function loadFromDatabase($locale, $group, $namespace) : array
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
    protected function loadGroup($locale, $group) : array
    {
        $translations = $this->translationRepository->getItems(null, $group);

        return $this->createFormattedArray($translations, $locale);
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
    protected function loadNamespaced($locale, $namespace, $group) : array
    {
        $translations = $this->translationRepository->getItems($namespace, $group);

        return $this->createFormattedArray($translations, $locale);
    }

    /**
     * Create a formatted array as if it was coming from the default loader.
     *
     * @param Collection $translations
     * @param string     $locale
     *
     * @return array
     */
    protected function createFormattedArray($translations, $locale) : array
    {
        $array = [];

        if ($translations) {
            foreach ($translations as $translation) {
                $value = $translation->getTranslation('values', $locale, true);

                if (!empty($value)) {
                    array_set($array, $translation->key, $value);
                }
            }
        }

        return $array;
    }

    /**
     * Get the Laravel File loader.
     *
     * @return FileLoader
     */
    public function getFileLoader() : FileLoader
    {
        return $this->laravelFileLoader;
    }

    public function addJsonPath($path)
    {
        //
    }
}
