<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain;

/**
 * Constants
 * A container for all constants used in this domain.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class Constants
{
    /**
     * EE Core Version Required for Add-on
     */
    const CORE_VERSION_REQUIRED = '4.9.37.p';


    /**
     * String used as the prefix for the registration tracker meta key for the RegistrationsNotifiedCommand
     */
    const REGISTRATION_TRACKER_PREFIX = 'ee_auen_processed_';

    /**
     * Represents the string used to reference the extra meta for holding the days before threshold.
     *
     * @var string
     */
    const DAYS_BEFORE_THRESHOLD_IDENTIFIER = 'automation_days_before';


    /**
     * Represents the string used to reference the extra meta for holding whether the automation is active or not.
     */
    const AUTOMATION_ACTIVE_IDENTIFIER = 'automation_active';


    /**
     * Equivalent to `__FILE__` for main plugin.
     *
     * @var string
     */
    private static $plugin_file = '';


    /**
     * Version for Add-on.
     *
     * @var string
     */
    private static $version = '1.0.0';


    /**
     * Initializes internal static properties.
     *
     * @param $plugin_file
     * @param $version
     */
    public static function init($plugin_file, $version)
    {
        self::$plugin_file = $plugin_file;
        self::$version     = $version;
    }


    /**
     * @return string
     */
    public static function pluginFile()
    {
        return self::$plugin_file;
    }

    /**
     * @return string
     */
    public static function pluginBasename()
    {
        return plugin_basename(self::$plugin_file);
    }

    /**
     * @return string
     */
    public static function pluginPath()
    {
        return plugin_dir_path(self::$plugin_file);
    }


    /**
     * @return string
     */
    public static function pluginUrl()
    {
        return plugin_dir_url(self::$plugin_file);
    }


    /**
     * @return string
     */
    public static function version()
    {
        return self::$version;
    }
}