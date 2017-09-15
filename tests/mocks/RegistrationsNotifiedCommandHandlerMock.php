<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\registration\RegistrationsNotifiedCommandHandler;

class RegistrationsNotifiedCommandHandlerMock extends RegistrationsNotifiedCommandHandler
{
    public function setRegistrationsProcessed(array $registrations, $context, $id_ref)
    {
        return parent::setRegistrationsProcessed($registrations, $context, $id_ref);
    }
}
