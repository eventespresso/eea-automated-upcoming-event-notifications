<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages;

use EE_Error;
use EE_Register_Messages_Shortcode_Library;
use EE_Automate_Upcoming_Datetime_message_type;
use EE_Automate_Upcoming_Event_message_type;
use EE_messenger;
use EE_message_type;
use EventEspresso\core\domain\DomainInterface;

/**
 * RegisterCustomShortcodes
 * Takes care of registering the custom shortcode library.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\messages
 * @author     Darren Ethier
 * @since      1.0.0
 */
class RegisterCustomShortcodeLibrary
{


    /**
     * @var DomainInterface
     */
    private $domain;


    /**
     * RegisterCustomShortcodes constructor.
     * Hooks are set in construct because it is _expected_ this class only gets instantiated once.
     * It is recommended to use the EventEspresso\core\services\loaders\Loader for instantiating/retrieving a shared
     * instance of this class which should ensure its only loaded once.
     *
     * @param DomainInterface $domain
     */
    public function __construct(DomainInterface $domain)
    {
        $this->domain = $domain;
        add_action(
            'EE_Brewing_Regular___messages_caf',
            array($this, 'registration')
        );
        add_action(
            'AHEE__EE_Register_Addon__deregister__after',
            array($this, 'deRegistration')
        );
        add_action(
            'FHEE__EE_Messages_Base__get_valid_shortcodes',
            array($this, 'modifyValidShortcodes'),
            10,
            2
        );
    }


    /**
     * Callback on `EE_Brewing_Regular___messages_caf` for registering the custom library.
     *
     * @throws EE_Error
     */
    public function registration()
    {
        EE_Register_Messages_Shortcode_Library::register(
            'specific_datetime_shortcode_library',
            array(
                'name'                    => 'specific_datetime',
                'autoloadpaths'           => $this->domain->pluginPath()
                                             . 'core/messages/shortcodes/',
                'msgr_validator_callback' => array($this, 'messengerValidatorCallback'),
            )
        );
    }


    /**
     * Callback for `AHEE__EE_Register_Addon__deregister__after` that ensures the custom shortcode library is
     * deregistered when the add-on is deregistered.
     *
     * @param $addon_name
     */
    public function deRegistration($addon_name)
    {
        if ($addon_name === 'Automated_Upcoming_Event_Notification') {
            EE_Register_Messages_Shortcode_Library::deregister('specific_datetime_shortcode_library');
        }
    }


    /**
     * Callback on `FHEE__EE_Messages_Base__get_valid_shortcodes` that is used to ensure the new shortcode library is
     * registered with the appropriate message type as a valid library.
     * Also using this to remove shortcodes we don't want exposed for the new message types.
     *
     * @param array           $valid_shortcodes Existing array of valid shortcodes.
     * @param EE_Message_Type $message_type
     * @return array
     */
    public function modifyValidShortcodes($valid_shortcodes, $message_type)
    {
        if ($message_type instanceof EE_Automate_Upcoming_Datetime_message_type) {
            $valid_shortcodes['admin'][] = 'specific_datetime';
            $valid_shortcodes['attendee'][] = 'specific_datetime';
        }
        if ($message_type instanceof EE_Automate_Upcoming_Datetime_message_type
            || $message_type instanceof EE_Automate_Upcoming_Event_message_type
        ) {
            // now we need to remove the primary_registrant shortcodes
            $shortcode_libraries_to_remove = array(
                'primary_registration_details',
                'primary_registration_list',
            );
            $contexts = array_keys($valid_shortcodes);
            foreach ($shortcode_libraries_to_remove as $shortcode_library_to_remove) {
                array_walk(
                    $contexts,
                    function ($context) use ($shortcode_library_to_remove, &$valid_shortcodes) {
                        $key_to_remove = array_search(
                            $shortcode_library_to_remove,
                            $valid_shortcodes[ $context ],
                            true
                        );
                        if ($key_to_remove !== false) {
                            unset($valid_shortcodes[ $context ][ $key_to_remove ]);
                        }
                    }
                );
            }
        }
        return $valid_shortcodes;
    }


    /**
     * Callback set (on registering a shortcode library) that handles the validation of this new library.
     *
     * @param array        $validator_config
     * @param EE_messenger $messenger
     * @return array
     */
    public function messengerValidatorCallback($validator_config, EE_messenger $messenger)
    {
        if ($messenger->name !== 'email') {
            return $validator_config;
        }
        $validator_config['content']['shortcodes'][] = 'specific_datetime';
        return $validator_config;
    }
}
