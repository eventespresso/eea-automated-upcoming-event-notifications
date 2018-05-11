<?php

namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler;
use EE_Message_Template_Group;
use EEM_Message_Template_Group;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBus;
use EventEspresso\core\services\loaders\Loader;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;

class SchedulerMock extends Scheduler
{
    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * SchedulerMock constructor.
     *
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        //not calling parent because for tests we don't want the constructor to run.
        $this->message_template_group_model = EEM_Message_Template_Group::instance();
        $this->loader = LoaderFactory::getLoader()->getShared(Loader::class);
        $this->command_bus = LoaderFactory::getLoader()->getShared(CommandBus::class);
    }


    /**
     * Expose protected method.
     *
     * @param $message_type
     * @return EE_Message_Template_Group[]
     * @throws EE_Error
     */
    public function getActiveMessageTemplateGroupsForAutomation($message_type)
    {
        return parent::getActiveMessageTemplateGroupsForAutomation($message_type);
    }
}