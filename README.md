[![StyleCI](https://styleci.io/repos/85751920/shield?branch=master)](https://styleci.io/repos/85751920)

#Laravel Database Translations

## Description

This package will allow you to manage your translation strings in the database while using the file translations as a 
fallback.

The fallback priority is as follows: 

Database current locale -> Database fallback locale -> File system current locale -> File system fallback locale

This is intentional as this package is meant to be a replacement for the file based language strings

## Usage
You can use the Laravel `trans('group.key')` or `__('group.key')` helpers to display your translations.

The blade `@lang('key')` directive also behaves the same way.

Namespacing works the same as before too 

In a Service Provider `app('translator')->addNamespace('namespace', 'path/to/namespaced/folder/lang');`

and `trans('namespace::group.key')`, `__('namepace::group.key')` or `@lang('namepace::group.key')` to display


### Importing translation files

To load your current translation files in to the database run `php artisan trans-db:load {locale}` where `{locale}` 
is a language code
    
For example `php artisan trans-db:load en` will load all the files for 'en' and `php artisan trans-db:load es` will 
load all the files for 'es'
   
*Note: this is a catch all function and will include any files in language namespaces you have added to your service 
providers as well as any in `resources/lang`*


#### Adding more translation keys

The included utility command `php artisan trans-db:load` is non-destructive so add your new key to the appropriate 
translation file and run the command again, your new key will appear in the database whilst preserving your modified 
translations

#### Working with translations

Add: `use MikeZange\LaravelDatabaseTranslation\Repositories\TranslationRepository;`

and inject it into your `__construct()` or method.

There are 2 included methods for updating translations via the translations repository.

1. `$translationRepository->updateTranslationById($id, $locale, $value, $overwrite = true)`

2. `$translationRepository->updateTranslations(Translation $line, $locale, $value, $overwrite = true)`

The second method requires an instance of the translation model.

The optional 4th parameter controls whether the new translation will overwrite the old one if it exists, default is `true`

## Requirements

- `PHP >=7.0`
- `Laravel ~5.4`


## Installation

0. As always: back up your database - I am not responsible for any data loss

1. Install the package via Composer: 

    `composer require mike-zange/laravel-database-translation`
    
2. **REPLACE** the following Service Provider in `config/app.php`:
    
    `Illuminate\Translation\TranslationServiceProvider::class,`
    
    With the one from this package:
    
    `MikeZange\LaravelDatabaseTranslation\TranslationServiceProvider::class,`
    
3. Publish the configuration file `database.translations.php`

    `php artisan vendor:publish --provider="MikeZange\LaravelDatabaseTranslation\TranslationServiceProvider"`
    
4. Make sure you have a locale set either via `app()->setLocale()` or in `config/app.php`

5. Edit the config at `config/database.translations.php` to your requirements, the defaults should be okay for most uses

6. Run `php artisan migrate` to create the database table
