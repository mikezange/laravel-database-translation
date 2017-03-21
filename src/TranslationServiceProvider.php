<?php

namespace MikeZange\LaravelDatabaseTranslation;

use Illuminate\Support\ServiceProvider as ServiceProvider;
use MikeZange\LaravelDatabaseTranslation\Commands\LoadTranslationsFromFiles;
use MikeZange\LaravelDatabaseTranslation\Commands\RemoveTranslationsForLocale;
use MikeZange\LaravelDatabaseTranslation\Loaders\DatabaseLoader;
use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Translation\Translator;

/**
 * Class TranslationServiceProvider
 * @package MikeZange\LaravelDatabaseTranslation
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/database.translations.php' => config_path('database.translations.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                LoadTranslationsFromFiles::class,
                RemoveTranslationsForLocale::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/database.translations.php', 'database.translations');

        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Register the translation loader
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseLoader(
                $app['files'],
                $app['path.lang'],
                $this->app->make(TranslationRepository::class),
                $this->app->make(CacheRepository::class)
            );
        });
    }
}
