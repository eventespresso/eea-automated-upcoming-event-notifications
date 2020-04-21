<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * PostDatetimeNotificationsCommand
 * Used when the command is for upcoming datetimes.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class PostDatetimeNotificationsCommand extends PostNotificationsCommand
{

    /**
     * @return string
     */
    protected function getMessageTypeNotificationIsFor()
    {
        return Domain::MESSAGE_TYPE_AUTOMATE_POST_DATETIME;
    }
}
