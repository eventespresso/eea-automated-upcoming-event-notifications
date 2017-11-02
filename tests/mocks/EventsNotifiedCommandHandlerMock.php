<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event\EventsNotifiedCommandHandler;

class EventsNotifiedCommandHandlerMock extends EventsNotifiedCommandHandler
{
    public function setEventsProcessed(array $events, $context)
    {
        return parent::setEventsProcessed($events, $context);
    }
}
