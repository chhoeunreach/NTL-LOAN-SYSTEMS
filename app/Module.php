<?php

/**
 * Minimal stand-in for the nwidart/laravel-modules "Module" facade.
 *
 * The standalone LoanManagement distribution does not ship with the
 * nwidart/laravel-modules package, but code copied from the parent POS
 * application (App\Utils\ModuleUtil) references the root "Module" facade.
 * This class provides just enough surface area for that code to run:
 * it reports that no external nwidart modules are registered.
 */
class Module
{
    /**
     * Check whether a module is available.
     *
     * @param  string  $module_name
     * @return bool
     */
    public static function has($module_name)
    {
        return false;
    }

    /**
     * Return all registered nwidart modules as a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function toCollection()
    {
        return collect();
    }

    /**
     * Return all enabled modules.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function allEnabled()
    {
        return collect();
    }

    /**
     * Check whether a module is enabled.
     *
     * @param  string  $module_name
     * @return bool
     */
    public static function isEnabled($module_name)
    {
        return false;
    }
}