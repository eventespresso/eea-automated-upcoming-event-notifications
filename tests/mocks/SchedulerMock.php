<?php

namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler;
use EE_Message_Template_Group;
use EEM_Message_Template_Group;
use EE_Registry;

class SchedulerMock extends Scheduler
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct()
    {
        //not calling parent because for tests we don't want the constructor to run.
        $this->message_template_group_model = EEM_Message_Template_Group::instance();
        $this->loader = EE_Registry::instance()->create('EventEspresso\core\services\loaders\Loader');
        $this->command_bus = EE_Registry::instance()->create('EventEspresso\core\services\commands\CommandBus');
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