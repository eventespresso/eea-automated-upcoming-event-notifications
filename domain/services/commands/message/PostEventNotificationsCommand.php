<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * PostEventNotificationsCommand
 * Used when the command is for upcoming events.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
 * @subpackage
 * @author  Tony Warwick
 * @since   $VID:$
 */
class PostEventNotificationsCommand extends PostNotificationsCommand
{

    /**
     * @return string
     */
    protected function getMessageTypeNotificationIsFor()
    {
        return Domain::MESSAGE_TYPE_AUTOMATE_POST_EVENT;
    }
}
