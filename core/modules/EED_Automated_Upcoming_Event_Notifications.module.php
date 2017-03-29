<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * Module for handling automated upcoming event and datetime notification sends.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage modules
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EED_Automated_Upcoming_Event_Notifications extends EED_Messages
{


    /**
     * Doing nothing but need to override EED_Messages method so the actions set there don't get registered twice.
     */
    public static function set_hooks()
    {
    }


    /**
     * Doing nothing but need to override EED_Messages method so the actions set there don't get registered twice.
     */
    public static function set_hooks_admin()
    {
    }


    /**
     * Preps and queues messages for the given message type name and data for sending.
     * @param string    $message_type_name
     * @param array     $data
     */
    public static function prep_and_queue_messages($message_type_name, array $data)
    {
        EE_Registry::instance()->load_helper('MSG_Template');
        //only continue if the message type is one of the allowed message types
        //to be processed by this handler or is active
        if (! in_array($message_type_name, self::allowed_message_types())
            || ! EEH_MSG_Template::is_mt_active($message_type_name)
        ) {
            return;
        }

        self::_load_controller();

        try {
            $messages_to_generate = self::$_MSG_PROCESSOR->setup_mtgs_for_all_active_messengers($message_type_name, $data);
            //batch queue and initiate request
            self::$_MSG_PROCESSOR->batch_queue_for_generation_and_persist($messages_to_generate);
            self::$_MSG_PROCESSOR->get_queue()->initiate_request_by_priority();
        } catch (EE_Error $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
    }


    /**
     * Returns an array of allowed message type names (slugs) for this module.
     * @return array
     */
    protected static function allowed_message_types()
    {
        return array('automated_upcoming_datetime', 'automated_upcoming_event');
    }
}