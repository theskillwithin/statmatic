<?php

namespace Statamic\API;

/**
 * Interacting with the configuration
 */
class Config
{
    /**
     * Get a config variable
     *
     * @param string      $key      The name of the key
     * @param mixed|bool  $default  The fallback value
     * @return mixed
     */
    public static function get($key, $default = false)
    {
        return array_get(datastore()->getScope('settings'), $key, $default);
    }

    /**
     * Get the app key
     *
     * @return string
     */
    public static function getAppKey()
    {
        return self::get('system.app_key');
    }

    /**
     * Get the license key
     *
     * @return string|null
     */
    public static function getLicenseKey()
    {
        $key = self::get('system.license_key');

        if (! $key || $key == '') {
            return null;
        }

        return $key;
    }

    /**
     * Get the active theme name
     *
     * @return string
     */
    public static function getThemeName()
    {
        return self::get('theming.theme', 'default');
    }

    /**
     * Get the current locale's full code for date string translations
     *
     * @return string
     */
    public static function getFullLocale()
    {
        return self::get('system.locales.' . LOCALE . '.full', 'en_US');
    }

    /**
     * Get the locale keys
     *
     * @return array
     */
    public static function getLocales()
    {
        return array_keys(self::get('system.locales'));
    }

    /**
     * Get the default locale
     *
     * @return mixed
     */
    public static function getDefaultLocale()
    {
        $locales = self::get('system.locales');

        return key($locales);
    }

    /**
     * Get the locales that aren't the current (or specified) one
     *
     * @param string|null $locale The locale to treat as the current one
     * @return array
     */
    public static function getOtherLocales($locale = null)
    {
        if (! $locale) {
            $locale = site_locale();
        }

        $locales = array_keys(self::get('system.locales'));

        return array_values(array_diff($locales, [$locale]));
    }

    /**
     * Get the site URL
     *
     * @param string|null $locale Optionally get the site url for a locale
     * @return mixed
     */
    public static function getSiteUrl($locale = null)
    {
        $locales = self::get('system.locales');

        $locale = $locale ?: site_locale();

        return Str::ensureRight(array_get($locales, $locale.'.url'), '/');
    }

    /**
     * Get routes
     *
     * @return array
     */
    public static function getRoutes()
    {
        return self::get('routes');
    }
}
