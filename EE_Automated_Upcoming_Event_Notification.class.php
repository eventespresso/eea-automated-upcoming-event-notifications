<?php

use EventEspresso\core\services\loaders\Loader;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');
// define the plugin directory path and URL
define(
    'EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_BASENAME',
    plugin_basename(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PLUGIN_FILE)
);
define('EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH', plugin_dir_path(__FILE__));
define('EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_URL', plugin_dir_url(__FILE__));
define(
    'EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_ADMIN',
    EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'admin' . DS . 'automated_upcoming_event_notification' . DS
);

/**
 * Class  EE_Automated_Upcoming_Event_Notification
 *
 * @package     Event Espresso
 * @subpackage  eea-automated-upcoming-event-notification
 * @author      Brent Christensen
 */
class EE_Automated_Upcoming_Event_Notification extends EE_Addon
{

    /**
     * Register the add-on
     * @throws \EE_Error
     */
    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Automated_Upcoming_Event_Notification',
            array(
                'version'               => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_VERSION,
                'plugin_slug'           => 'eea_automated_upcoming_event_notifications',
                'min_core_version'      => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_CORE_VERSION_REQUIRED,
                'main_file_path'        => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PLUGIN_FILE,
                'pue_options'           => array(
                    'pue_plugin_slug' => 'eea-automated-upcoming-event-notifications',
                    'plugin_basename' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_BASENAME,
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
                'message_types' => array(
                   'automate_upcoming_event' => self::get_message_type_settings(
                       'EE_Automate_Upcoming_Event_message_type.class.php'
                   ),
                   'automate_upcoming_datetime' => self::get_message_type_settings(
                       'EE_Automate_Upcoming_Datetime_message_type.class.php'
                   )
                ),
                'module_paths' => array(
                    EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH
                        . 'Domain/Services/Modules/EED_Automated_Upcoming_Event_Notifications.module.php',
                    EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH
                        . 'Domain/Services/Modules/EED_Automated_Upcoming_Event_Notification_Messages.module.php',
                ),
                'namespace' => array(
                    'FQNS' => 'EventEspresso\AutomatedUpcomingEventNotifications',
                    'DIR' => __DIR__
                )
            )
        );
    }


    /**
     * Register things that have to happen early in loading.
     *
     */
    public function after_registration()
    {
        //load loader
        add_action(
            'AHEE__EE_System__load_espresso_addons__complete',
            array(
                __CLASS__,
                'loader'
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\Domain\Services\Admin\Controller',
            array(
                'EE_Request' => EE_Dependency_Map::load_from_cache
            )
        );

        //these have to happen earlier than module loading but after add-ons are loaded because
        //the modules `set_hooks` methods run at `init 9`.
        add_action(
            'AHEE__EE_System__load_espresso_addons__complete',
            function () {
                EE_Automated_Upcoming_Event_Notification::loader()->load(
                    '\EventEspresso\AutomatedUpcomingEventNotifications\Domain\Services\Tasks\Scheduler'
                );
                EE_Automated_Upcoming_Event_Notification::loader()->load(
                    'EventEspresso\AutomatedUpcomingEventNotifications\Domain\Messages\Services\RegisterCustomShortcodeLibrary'
                );
            },
            15
        );
    }


    /**
     * Callback for `AHEE__EE_System__load_espresso_addons__complete
     * This is also a method third party devs can use to grab the instance of this class for unsetting any hooks/actions
     * using this instance.
     * @param bool $reset  Used to force a reset of the $loader
     * @return Loader
     */
    public static function loader($reset = false)
    {
        static $loader;
        if (! $loader instanceof Loader
            || $reset
        ) {
            $loader = new Loader();
        }
        return $loader;
    }

    /**
     * Return the settings array for the message type.
     * @param string $mtfilename  The filename for the message type.
     * @return array
     */
    protected static function get_message_type_settings($mtfilename)
    {
        return array(
            'mtfilename' => $mtfilename,
            'autoloadpaths' => array(
                EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'Domain/Messages/'
            ),
            'messengers_to_activate_with' => array('email'),
            'messengers_to_validate_with' => array('email'),
            'force_activation' => true,
            'messengers_supporting_default_template_pack_with' => array('email'),
            'base_path_for_default_templates' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'Views/Messages/templates/',
            'base_path_for_default_variation' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'Views/Messages/variations/',
            'base_url_for_default_variation' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'Views/Messages/variations/'
        );
    }




}
// End of file EE_Automated_Upcoming_Event_Notification.class.php
// Location: wp-content/plugins/eea-automated-upcoming-event-notification/EE_Automated_Upcoming_Event_Notification.class.php
