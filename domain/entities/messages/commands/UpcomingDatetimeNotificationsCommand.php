<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\messages\commands;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingDatetimeNotificationsCommand
 * Used when the command is for upcoming datetimes.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\messages\commands
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class UpcomingDatetimeNotificationsCommand extends UpcomingNotificationsCommand
{

    /**
     * @return string
     */
    protected function getMessageTypeNotificationIsFor()
    {
        return 'automate_upcoming_datetime';
    }
}
