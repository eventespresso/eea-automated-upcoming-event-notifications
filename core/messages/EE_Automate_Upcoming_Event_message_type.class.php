<?php
defined('EVENT_ESPRESSO') || exit('No direct access.');

/**
 * Message type for automated upcoming event notifications.
 * On a daily cron schedule, this message type will grab all the events where the earliest datetime is within the
 * threshold set by the user (i.e. within x days), then send a notification based on the template generated for this
 * message type.  No matter how many datetimes are present on the event, the notification for this message type is only
 * sent ONCE.
 *
 */
class EE_Automate_Upcoming_Event_message_type extends EE_Registration_Base_message_type
{

    public function __construct()
    {

    }

    /**
     * _set_contexts
     * This sets up the contexts associated with the message_type
     *
     * @access  protected
     * @return  void
     */
    protected function _set_contexts()
    {

    }
}