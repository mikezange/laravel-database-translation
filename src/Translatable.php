<?php

namespace MikeZange\LaravelDatabaseTranslation;

use Spatie\Translatable\HasTranslations;

trait Translatable
{
    use HasTranslations;

    /**
     * @param string $key
     *
     * @param string $locale
     * @param bool $useFallbackLocale
     *
     * @return mixed|string
     */


    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale) : string
    {
        if (in_array($locale, $this->getTranslatedLocales($key))) {
            return $locale;
        }

        if (!$useFallbackLocale) {
            return $locale;
        }

        if (!is_null($fallbackLocale = config('app.fallback_locale'))) {
            return $fallbackLocale;
        }

        return $locale;
    }
}
