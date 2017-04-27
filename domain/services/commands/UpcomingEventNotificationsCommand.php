<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingEventNotificationsCommand
 * Used when the command is for upcoming events.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class UpcomingEventNotificationsCommand extends UpcomingNotificationsCommand
{
    protected function getMessageTypeNotificationIsFor()
    {
        return 'automate_upcoming_event';
    }
}