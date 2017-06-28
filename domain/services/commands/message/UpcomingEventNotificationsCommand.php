<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingEventNotificationsCommand
 * Used when the command is for upcoming events.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class UpcomingEventNotificationsCommand extends UpcomingNotificationsCommand
{

    /**
     * @return string
     */
    protected function getMessageTypeNotificationIsFor()
    {
        return 'automate_upcoming_event';
    }
}