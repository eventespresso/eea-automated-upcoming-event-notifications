<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain;

use DomainException;
use EE_Addon;
use EE_Base_Class;
use EE_Dependency_Map;
use EE_Error;
use EE_Message_Template_Group;
use EE_Register_Addon;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\Controller;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\ItemsNotifiedCommandHandler;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingDatetimeNotificationsCommandHandler;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingEventNotificationsCommandHandler;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingNotificationsCommandHandler;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\RegisterCustomShortcodeLibrary;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler;
use EventEspresso\core\domain\DomainInterface;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use EventEspresso\core\services\loaders\Loader;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\core\services\request\Request;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class  EE_Automated_Upcoming_Event_Notification
 *
 * @package     Event Espresso
 * @subpackage  eea-automated-upcoming-event-notification
 * @author      Brent Christensen
 */
class AutomatedUpcomingEventNotifications extends EE_Addon
{

    /**
     * @var LoaderInterface
     */
    private $loader;


    /**
     * AutomatedUpcomingEventNotifications constructor.
     *
     * @param EE_Dependency_Map $dependency_map
     * @param DomainInterface   $domain
     * @param LoaderInterface   $loader
     */
    public function __construct(
        EE_Dependency_Map $dependency_map,
        DomainInterface $domain,
        LoaderInterface $loader
    ) {
        $this->loader = $loader;
        parent::__construct($dependency_map, $domain);
    }


    /**
     * Register the add-on
     *
     * @param Domain $domain
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function registerAddon(Domain $domain)
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Automated_Upcoming_Event_Notification',
            [
                'class_name'       => self::class,
                'version'          => $domain->version(),
                'plugin_slug'      => 'eea_automated_upcoming_event_notifications',
                'min_core_version' => Domain::CORE_VERSION_REQUIRED,
                'min_wp_version'   => Domain::WP_VERSION_REQUIRED,
                'main_file_path'   => $domain->pluginFile(),
                'domain_fqcn'      => Domain::class,
                'pue_options'      => [
                    'pue_plugin_slug' => 'eea-automated-upcoming-event-notifications',
                    'plugin_basename' => $domain->pluginBasename(),
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ],
                'message_types'    => [
                    Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT    => self::getMessageTypeSettings(
                        'EE_Automate_Upcoming_Event_message_type.class.php',
                        $domain
                    ),
                    Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME => self::getMessageTypeSettings(
                        'EE_Automate_Upcoming_Datetime_message_type.class.php',
                        $domain
                    ),
                ],
                'module_paths'     => [
                    $domain->pluginPath()
                    . 'domain/services/modules/message/EED_Automated_Upcoming_Event_Notification_Messages.module.php',
                ],
                'autoloader_paths' => [
                    'EE_Specific_Datetime_Shortcodes' => $domain->pluginPath()
                                                         . 'domain/services/messages/EE_Specific_Datetime_Shortcodes.lib.php',
                ],
            ]
        );
    }


    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps


    /**
     * Register things that have to happen early in loading.
     */
    public function after_registration()
    {
        $this->registerDependencies();
        // these have to happen earlier than module loading but after add-ons are loaded because
        // the modules `set_hooks` methods run at `init 9`.
        $this->loader->getShared(Scheduler::class);
        $this->loader->getShared(RegisterCustomShortcodeLibrary::class);
        $loader = $this->loader;
        add_action(
            'admin_init',
            function () use ($loader) {
                $loader->getShared(Controller::class);
            }
        );
        add_filter(
            'FHEE__EE_Base_Class__get_extra_meta__default_value',
            [$this, 'setDefaultActiveStateForMessageTypes'],
            10,
            4
        );
    }

    // phpcs:enable


    /**
     * Return the settings array for the message type.
     *
     * @param string          $mtfilename The filename for the message type.
     * @param DomainInterface $domain
     * @return array
     */
    protected static function getMessageTypeSettings($mtfilename, DomainInterface $domain)
    {
        return [
            'mtfilename'                                       => $mtfilename,
            'autoloadpaths'                                    => [
                $domain->pluginPath() . 'domain/entities/message',
                $domain->pluginPath() . 'domain/services/messages',
            ],
            'messengers_to_activate_with'                      => ['email'],
            'messengers_to_validate_with'                      => ['email'],
            'force_activation'                                 => true,
            'messengers_supporting_default_template_pack_with' => ['email'],
            'base_path_for_default_templates'                  => $domain->pluginPath() . 'views/message/templates/',
            'base_path_for_default_variation'                  => $domain->pluginPath() . 'views/message/variations/',
            'base_url_for_default_variation'                   => $domain->pluginPath() . 'views/message/variations/',
        ];
    }


    /**
     * Take care of registering any dependencies needed by this add-on
     */
    protected function registerDependencies()
    {
        $this->dependencyMap()->registerDependencies(
            Controller::class,
            [Request::class => EE_Dependency_Map::load_from_cache]
        );
        $this->dependencyMap()->registerDependencies(
            RegisterCustomShortcodeLibrary::class,
            [Domain::class => EE_Dependency_Map::load_from_cache]
        );
        $this->dependencyMap()->registerDependencies(
            Scheduler::class,
            [
                CommandBusInterface::class   => EE_Dependency_Map::load_from_cache,
                'EEM_Message_Template_Group' => EE_Dependency_Map::load_from_cache,
                Loader::class                => EE_Dependency_Map::load_from_cache,
            ]
        );
        $this->dependencyMap()->registerDependencies(
            UpcomingDatetimeNotificationsCommandHandler::class,
            [
                CommandBusInterface::class                   => EE_Dependency_Map::load_from_cache,
                CommandFactoryInterface::class               => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                           => EE_Dependency_Map::load_from_cache,
                'EEM_Datetime'                               => EE_Dependency_Map::load_from_cache,
                SplitRegistrationDataRecordForBatches::class => EE_Dependency_Map::load_from_cache,
            ]
        );
        $this->dependencyMap()->registerDependencies(
            UpcomingNotificationsCommandHandler::class,
            [
                CommandBusInterface::class                   => EE_Dependency_Map::load_from_cache,
                CommandFactoryInterface::class               => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                           => EE_Dependency_Map::load_from_cache,
                SplitRegistrationDataRecordForBatches::class => EE_Dependency_Map::load_from_cache,
            ]
        );
        $this->dependencyMap()->registerDependencies(
            UpcomingEventNotificationsCommandHandler::class,
            [
                CommandBusInterface::class                   => EE_Dependency_Map::load_from_cache,
                CommandFactoryInterface::class               => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                           => EE_Dependency_Map::load_from_cache,
                'EEM_Event'                                  => EE_Dependency_Map::load_from_cache,
                SplitRegistrationDataRecordForBatches::class => EE_Dependency_Map::load_from_cache,
            ]
        );
        $this->dependencyMap()->registerDependencies(
            ItemsNotifiedCommandHandler::class,
            ['EEM_Extra_Meta' => EE_Dependency_Map::load_from_cache]
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
        // only modify default for the active context meta key
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
