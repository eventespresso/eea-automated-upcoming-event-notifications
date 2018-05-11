<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;

/**
 * Module for handling automated upcoming event and datetime notification sends.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage modules
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EED_Automated_Upcoming_Event_Notification_Messages extends EED_Messages
{


    /**
     * Doing nothing but need to override EED_Messages method so the actions set there don't get registered twice.
     */
    public static function set_hooks()
    {
        // noop
    }


    /**
     * Doing nothing but need to override EED_Messages method so the actions set there don't get registered twice.
     */
    public static function set_hooks_admin()
    {
        // noop
    }


    /**
     * Preps and queues messages for the given message type name, data and context for sending.
     *
     * @param string $message_type_name
     * @param array  $data
     * @param string $context
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public static function prep_and_queue_messages($message_type_name, array $data, $context)
    {
        // only continue if the message type is one of the allowed message types
        // to be processed by this handler or is active
        if (! EEH_MSG_Template::is_mt_active($message_type_name)
            || ! in_array($message_type_name, self::allowed_message_types(), true)
        ) {
            return;
        }
        self::_load_controller();

        try {
            $messages_to_generate = self::setup_messages_to_generate_for_all_active_messengers(
                $message_type_name,
                $data,
                $context
            );
            if ($messages_to_generate) {
                self::$_MSG_PROCESSOR->batch_queue_for_generation_and_persist($messages_to_generate);
                self::$_MSG_PROCESSOR->get_queue()->initiate_request_by_priority();
            }
        } catch (EE_Error $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
    }


    /**
     * Returns an array of allowed message type names (slugs) for this module.
     *
     * @return array
     */
    protected static function allowed_message_types()
    {
        return array(Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME, Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT);
    }


    /**
     * Loops through all active messengers and takes care of setting up the EE_Message_To_Generate objects.
     *
     * @param       $message_type
     * @param array $data
     * @param       $context
     * @return EE_Message_To_Generate[]
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected static function setup_messages_to_generate_for_all_active_messengers($message_type, array $data, $context)
    {
        self::_load_controller();
        $messages_to_generate = array();
        foreach (self::$_message_resource_manager->active_messengers() as $messenger_slug => $messenger_object) {
            $message_to_generate = new EE_Message_To_Generate($messenger_slug, $message_type, $data, $context);
            if ($message_to_generate->valid()) {
                $messages_to_generate[] = $message_to_generate;
            }
        }
        return $messages_to_generate;
    }
}
