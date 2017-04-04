<?php

namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\core\tasks\Scheduler;
use EE_Message_Template_Group;

class SchedulerMock extends Scheduler
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        //not calling parent because for tests we don't want the constructor to run.
    }


    /**
     * Expose protected method.
     * @param $message_type
     * @return EE_Message_Template_Group[]
     */
    public function getActiveMessageTemplateGroupsForAutomation($message_type)
    {
        return parent::getActiveMessageTemplateGroupsForAutomation($message_type);
    }
}