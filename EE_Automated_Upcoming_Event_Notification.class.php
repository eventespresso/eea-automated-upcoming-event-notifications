<?php

use EventEspresso\core\services\loaders\Loader;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');

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
     * @var LoaderInterface $loader;
     */
    private static $loader;



    /**
     * EE_Automated_Upcoming_Event_Notification constructor.
     *
     * @param LoaderInterface $loader
     */
    public function __construct(LoaderInterface $loader = null)
    {
        EE_Automated_Upcoming_Event_Notification::$loader = $loader instanceof LoaderInterface
            ? $loader
            : new Loader;
        parent::__construct();
    }

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
                'version'               => Constants::version(),
                'plugin_slug'           => 'eea_automated_upcoming_event_notifications',
                'min_core_version'      => Constants::CORE_VERSION_REQUIRED,
                'main_file_path'        => Constants::pluginFile(),
                'pue_options'           => array(
                    'pue_plugin_slug' => 'eea-automated-upcoming-event-notifications',
                    'plugin_basename' => Constants::pluginBasename(),
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
                    Constants::pluginPath()
                        . 'domain/services/modules/EED_Automated_Upcoming_Event_Notifications.module.php',
                    Constants::pluginPath()
                        . 'domain/services/modules/EED_Automated_Upcoming_Event_Notification_Messages.module.php',
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
        self::register_dependencies();
        //these have to happen earlier than module loading but after add-ons are loaded because
        //the modules `set_hooks` methods run at `init 9`.
        add_action(
            'AHEE__EE_System__load_espresso_addons__complete',
            function () {
                EE_Automated_Upcoming_Event_Notification::loader()->load(
                    '\EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler'
                );
                EE_Automated_Upcoming_Event_Notification::loader()->load(
                    'EventEspresso\AutomatedUpcomingEventNotifications\domain\messages\services\RegisterCustomShortcodeLibrary'
                );
            },
            15
        );
    }



    /**
     * Callback for `AHEE__EE_System__load_espresso_addons__complete
     * This is also a method third party devs can use to grab the instance of this class for unsetting any hooks/actions
     * using this instance.
     * @return LoaderInterface
     */
    public static function loader()
    {
        return EE_Automated_Upcoming_Event_Notification::$loader;
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
                Constants::pluginPath() . 'domain/messages'
            ),
            'messengers_to_activate_with' => array('email'),
            'messengers_to_validate_with' => array('email'),
            'force_activation' => true,
            'messengers_supporting_default_template_pack_with' => array('email'),
            'base_path_for_default_templates' => Constants::pluginPath() . 'views/messages/templates/',
            'base_path_for_default_variation' => Constants::pluginPath() . 'views/messages/variations/',
            'base_url_for_default_variation' => Constants::pluginPath() . 'views/messages/variations/'
        );
    }


    /**
     * Take care of registering any dependencies needed by this add-on
     */
    protected static function register_dependencies()
    {
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\Controller',
            array(
                'EE_Request' => EE_Dependency_Map::load_from_cache
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\UpcomingDatetimeNotificationsCommandHandler',
            array(
                'EEM_Registration' => EE_Dependency_Map::load_from_cache,
                'EEM_Datetime' => EE_Dependency_Map::load_from_cache,
                'EE_Registry' => EE_Dependency_Map::load_from_cache
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\UpcomingNotificationsCommandHandler',
            array(
                'EEM_Registration' => EE_Dependency_Map::load_from_cache,
                'EE_Registry' => EE_Dependency_Map::load_from_cache
            )
        );
    }

}
