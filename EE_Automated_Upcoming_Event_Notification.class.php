<?php
defined('EVENT_ESPRESSO_VERSION') || exit;
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
        /**
         * @todo:
         * - make sure ticketing shortcodes are registered (I think they should just "show up" we'll see)
         * - call/set cron schedule for these two message types.
         */
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
                EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'core/messages/'
            ),
            'messengers_to_activate_with' => array('email'),
            'messengers_to_validate_with' => array('email'),
            'force_activation' => true,
            'messengers_supporting_default_template_pack_with' => array('email'),
            'base_path_for_default_templates' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'core/messages/templates/',
            'base_path_for_default_variation' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'core/messages/variations/',
            'base_url_for_default_variation' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'core/messages/variations/'
        );
    }


}
// End of file EE_Automated_Upcoming_Event_Notification.class.php
// Location: wp-content/plugins/eea-automated-upcoming-event-notification/EE_Automated_Upcoming_Event_Notification.class.php
