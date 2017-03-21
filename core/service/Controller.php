<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\service;

use EventEspresso\AutomatedUpcomingEventNotifications\core\factory\Registry;
use EE_Register_Messages_Shortcode_Library;
use EE_Automate_Upcoming_Datetime_message_type;
use EE_Automate_Upcoming_Event_message_type;
use EE_messenger;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');

class Controller
{
    /**
     * @var Registry
     */
    private $registry;


    /**
     * Controller constructor.
     * Sets up all the initial logic required for hooking this add-on in to various parts of EE.
     * This should only ever be called once.
     *
     * Client code can get the controller instance (for unsetting actions/filters etc) via
     * EE_Automated_Upcoming_Event_Notification::controller()
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
        $this->registerCustomShortcodeLibrary();
        add_action(
            'FHEE__EE_Messages_Base__get_valid_shortcodes',
            array($this, 'modifyValidShortcodes'),
            10,
            2
        );
        $this->setAdminHooks();

        //startCronScheduler
        $this->registry->call('\EventEspresso\AutomatedUpcomingEventNotifications\core\tasks\Scheduler');
    }


    /**
     * Takes care of registering the custom shortcode library for this add-on
     */
    protected function registerCustomShortcodeLibrary()
    {
        //ya intentionally using closures here.  If client code want's this library to not be registered there's facility
        //for deregistering via the provided api.  This forces client code to use that api.
        add_action('EE_Brewing_Regular___messages_caf', function () {
            //Register custom shortcode library used by this add-on
            EE_Register_Messages_Shortcode_Library::register(
                'specific_datetime_shortcode_library',
                array(
                    'name'                    => 'specific_datetime',
                    'autoloadpaths'           => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH . 'core/messages/shortcodes/',
                    'msgr_validator_callback' => array($this, 'messengerValidatorCallback')
                )
            );
        }, 20);
        // make sure the shortcode library is deregistered if this add-on is deregistered.
        add_action('AHEE__EE_Register_Addon__deregister__after', function ($addon_name) {
            if ($addon_name === 'Automated_Upcoming_Event_Notification') {
                EE_Register_Messages_Shortcode_Library::deregister('specific_datetime_shortcode_library');
            }
        });
    }


    /**
     * Callback set (on registering a shortcode library) that handles the validation of this new library.
     * Also using this to remove shortcodes we don't want exposed for the new message types.
     * @param array             $valid_shortcodes   Existing array of valid shortcodes.
     * @param EE_Message_Type   $message_type
     * @return array
     */
    public function modifyValidShortcodes($valid_shortcodes, $message_type)
    {
        if ($message_type instanceof EE_Automate_Upcoming_Datetime_message_type) {
            $valid_shortcodes['admin'][]    = 'specific_datetime';
            $valid_shortcodes['attendee'][] = 'specific_datetime';
        }

        if ($message_type instanceof EE_Automate_Upcoming_Datetime_message_type
            || $message_type instanceof EE_Automate_Upcoming_Event_message_type
        ) {
            //now we need to remove the primary_registrant shortcodes
            $shortcode_libraries_to_remove = array(
                'primary_registration_details',
                'primary_registration_list'
            );
            $contexts = array_keys($valid_shortcodes);
            foreach ($shortcode_libraries_to_remove as $shortcode_library_to_remove) {
                array_walk(
                    $contexts,
                    function ($context) use ($shortcode_library_to_remove, &$valid_shortcodes) {
                        $key_to_remove = array_search(
                            $shortcode_library_to_remove,
                            $valid_shortcodes[$context]
                        );
                        if ($key_to_remove !== false) {
                            unset($valid_shortcodes[$context][$key_to_remove]);
                        }
                    }
                );
            }
        }
        return $valid_shortcodes;
    }


    public function messengerValidatorCallback($validator_config, EE_messenger $messenger)
    {
        if ($messenger->name !== 'email') {
            return $validator_config;
        }

        $validator_config['content']['shortcodes'][] = 'specific_datetime';
        return $validator_config;
    }



    protected function setAdminHooks()
    {
        add_action(
            'AHEE__EE_Admin__loaded',
            array($this, 'loadAdminController')
        );
    }


    public function loadAdminController()
    {
        $this->registry->call('\EventEspresso\AutomatedUpcomingEventNotifications\core\messages\admin\Controller');
    }
}