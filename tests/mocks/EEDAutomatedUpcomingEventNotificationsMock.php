<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EED_Automated_Upcoming_Event_Notifications;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class EEDAutomatedUpcomingEventNotificationsMock extends EED_Automated_Upcoming_Event_Notifications
{
    /**
     * Expose processor for tests.
     * @return \EE_Messages_Processor
     */
    public static function getProcessor()
    {
        return self::$_MSG_PROCESSOR;
    }
}