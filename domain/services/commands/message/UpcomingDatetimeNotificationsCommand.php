<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * UpcomingDatetimeNotificationsCommand
 * Used when the command is for upcoming datetimes.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
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
        return Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME;
    }
}
