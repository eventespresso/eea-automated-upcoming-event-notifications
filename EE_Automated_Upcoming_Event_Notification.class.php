<?php
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

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
     * @var LoaderInterface $loader ;
     */
    private static $loader;


    /**
     * EE_Automated_Upcoming_Event_Notification constructor.
     *
     * @param LoaderInterface $loader
     */
    public function __construct(LoaderInterface $loader = null)
    {
        EE_Automated_Upcoming_Event_Notification::$loader = $loader;
        parent::__construct();
    }



    /**
     * Register the add-on
     *
     * @throws EE_Error
     * @throws DomainException
     */
    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Automated_Upcoming_Event_Notification',
            array(
                'version'          => Domain::version(),
                'plugin_slug'      => 'eea_automated_upcoming_event_notifications',
                'min_core_version' => Domain::CORE_VERSION_REQUIRED,
                'main_file_path'   => Domain::pluginFile(),
                'pue_options'      => array(
                    'pue_plugin_slug' => 'eea-automated-upcoming-event-notifications',
                    'plugin_basename' => Domain::pluginBasename(),
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
                'message_types'    => array(
                    Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT    => self::get_message_type_settings(
                        'EE_Automate_Upcoming_Event_message_type.class.php'
                    ),
                    Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME => self::get_message_type_settings(
                        'EE_Automate_Upcoming_Datetime_message_type.class.php'
                    ),
                ),
                'module_paths'     => array(
                    Domain::pluginPath()
                    . 'domain/services/modules/message/EED_Automated_Upcoming_Event_Notifications.module.php',
                    Domain::pluginPath()
                    . 'domain/services/modules/message/EED_Automated_Upcoming_Event_Notification_Messages.module.php',
                ),
                'namespace'        => array(
                    'FQNS' => 'EventEspresso\AutomatedUpcomingEventNotifications',
                    'DIR'  => __DIR__,
                ),
            )
        );
    }


    /**
     * Register things that have to happen early in loading.
     *
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public function after_registration()
    {
        self::register_dependencies();
        //these have to happen earlier than module loading but after add-ons are loaded because
        //the modules `set_hooks` methods run at `init 9`.

        EE_Automated_Upcoming_Event_Notification::loader()->getShared(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler'
        );
        EE_Automated_Upcoming_Event_Notification::loader()->getShared(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\RegisterCustomShortcodeLibrary'
        );
        add_filter(
            'FHEE__EE_Base_Class__get_extra_meta__default_value',
            array($this, 'setDefaultActiveStateForMessageTypes'),
            10,
            4
        );
    }


    /**
     * @return LoaderInterface
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function loader()
    {
        if (! EE_Automated_Upcoming_Event_Notification::$loader instanceof LoaderInterface) {
            EE_Automated_Upcoming_Event_Notification::$loader = LoaderFactory::getLoader();
        }
        return EE_Automated_Upcoming_Event_Notification::$loader;
    }



    /**
     * Return the settings array for the message type.
     *
     * @param string $mtfilename The filename for the message type.
     * @return array
     * @throws DomainException
     */
    protected static function get_message_type_settings($mtfilename)
    {
        return array(
            'mtfilename'                                       => $mtfilename,
            'autoloadpaths'                                    => array(
                Domain::pluginPath() . 'domain/entities/message',
                Domain::pluginPath() . 'domain/services/messages'
            ),
            'messengers_to_activate_with'                      => array('email'),
            'messengers_to_validate_with'                      => array('email'),
            'force_activation'                                 => true,
            'messengers_supporting_default_template_pack_with' => array('email'),
            'base_path_for_default_templates'                  => Domain::pluginPath() . 'views/message/templates/',
            'base_path_for_default_variation'                  => Domain::pluginPath() . 'views/message/variations/',
            'base_url_for_default_variation'                   => Domain::pluginPath() . 'views/message/variations/',
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
                'EE_Request' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler',
            array(
                'EventEspresso\core\services\commands\CommandBusInterface' => EE_Dependency_Map::load_from_cache,
                'EEM_Message_Template_Group' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\loaders\Loader' => EE_Dependency_Map::load_from_cache
            )

        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingDatetimeNotificationsCommandHandler',
            array(
                'EventEspresso\core\services\commands\CommandBusInterface' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandFactoryInterface' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration' => EE_Dependency_Map::load_from_cache,
                'EEM_Datetime'     => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingNotificationsCommandHandler',
            array(
                'EventEspresso\core\services\commands\CommandBusInterface' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandFactoryInterface' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingEventNotificationsCommandHandler',
            array(
                'EventEspresso\core\services\commands\CommandBusInterface' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandFactoryInterface' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration' => EE_Dependency_Map::load_from_cache,
            )
        );
    }


    /**
     * Callback for FHEE__EE_Base_Class__get_extra_meta__default_value which is being used to ensure the default active
     * state for our new message types is false.
     *
     * @param               $default
     * @param               $meta_key
     * @param               $single
     * @param EE_Base_Class $model
     * @return bool
     * @throws EE_Error
     */
    public function setDefaultActiveStateForMessageTypes(
        $default,
        $meta_key,
        $single,
        EE_Base_Class $model
    ) {
        //only modify default for the active context meta key
        if ($model instanceof EE_Message_Template_Group
            && strpos($meta_key, EE_Message_Template_Group::ACTIVE_CONTEXT_RECORD_META_KEY_PREFIX) !== false
            && ($model->message_type() === Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME
                || $model->message_type() === Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT
            )
        ) {
            return false;
        }
        return $default;
    }
}
