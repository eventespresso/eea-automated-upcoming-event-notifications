<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EEM_Base;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\ItemsNotifiedCommandHandler;

class ItemsNotifiedCommandHandlerMock extends ItemsNotifiedCommandHandler
{
    public function setItemsProcessed(array $items, EEM_Base $model, $context)
    {
        return parent::setItemsProcessed($items, $model, $context);
    }
}
