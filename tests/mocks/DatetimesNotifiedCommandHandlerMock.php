<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\datetime\DatetimesNotifiedCommandHandler;

class DatetimesNotifiedCommandHandlerMock extends DatetimesNotifiedCommandHandler
{
    public function setDatetimesProcessed(array $datetimes, $context)
    {
        return parent::setDatetimesProcessed($datetimes, $context);
    }
}
