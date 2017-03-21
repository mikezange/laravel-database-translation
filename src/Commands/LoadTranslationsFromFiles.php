<?php

namespace MikeZange\LaravelDatabaseTranslation\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;

/**
 * Class LoadTranslationsFromFiles
 * @package MikeZange\LaravelDatabaseTranslation\Commands
 */
class LoadTranslationsFromFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trans-db:load {locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads the translations from file into the DB for a given locale';

    /**
     * The Laravel File Loader
     *
     * @var FileLoader
     */
    protected $laravelLoader;

    /**
     * Translation repository
     *
     * @var TranslationRepository
     */
    protected $translationRepository;

    /**
     * Laravel Filesystem
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The locale
     *
     * @var string
     */
    protected $locale;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $filesystem
     * @param TranslationRepository $translationRepository
     */
    public function __construct(Filesystem $filesystem, TranslationRepository $translationRepository)
    {
        parent::__construct();

        //this is so we can benefit from namespaces that are already loaded
        $this->laravelLoader = app()['translator']->getLoader()->getFileLoader();
        $this->translationRepository = $translationRepository;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->locale = $this->argument('locale');

        $this->loadGroupLines();

        $this->loadNameSpacedLines();

        return $this->info('Complete');
    }

    protected function loadGroupLines()
    {
        $langFiles = $this->getLangFiles(resource_path() . '/lang');

        $this->processFiles($langFiles);
    }


    protected function loadNameSpacedLines()
    {
        foreach ($this->laravelLoader->namespaces() as $namespace => $path) {
            $langFiles = $this->getLangFiles($path);
            $this->processFiles($langFiles, $namespace);
        }
    }

    /**
     * @param $files
     * @param null|string $namespace
     */
    protected function processFiles($files, $namespace = null)
    {
        foreach ($files as $file) {
            $group = $this->getGroup($file);
            $lines = $this->getLines($file);
            $this->loadLines($lines, $namespace, $group, $this->locale);
        }
    }

    /**
     * Load the lines into the database
     * @param $lines
     * @param $namespace
     * @param $group
     * @param $locale
     */
    protected function loadLines($lines, $namespace, $group, $locale)
    {
        foreach ($lines as $key => $value) {
            $line = $this->translationRepository->getItem($namespace, $group, $key);
            if ($line) {
                $this->translationRepository->updateTranslations($line, $this->locale, $value, false);
            } else {
                $attributes = [
                    'namespace' => $namespace,
                    'group' => $group,
                    'key' => $key,
                    'values' => [
                        "{$locale}" => $value
                    ]
                ];
                $this->translationRepository->create($attributes);
            }
        }
    }

    /**
     * @param $file
     *
     * @return mixed
     */
    protected function getGroup($file)
    {
        preg_match('/'.$this->locale.'\/([^\[]+).php/i', $file, $group);
        return $group[1];
    }

    /**
     * @param $file
     *
     * @return array
     */
    protected function getLines($file)
    {
        return array_dot($this->filesystem->getRequire($file));
    }

    /**
     * @param $path
     *
     * @return array
     */
    protected function getLangFiles($path)
    {
        $dir = $this->getLocaleDirPath($path);

        return $this->filesystem->files($dir);
    }

    /**
     * @param $path
     *
     * @return string
     */
    protected function getLocaleDirPath($path)
    {
        return $path . '/' . $this->locale;
    }
}
