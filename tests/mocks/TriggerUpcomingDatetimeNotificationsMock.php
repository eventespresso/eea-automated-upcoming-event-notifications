<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\core\tasks\TriggerUpcomingDatetimeNotifications;
use EE_Message_Template_Group;

/**
 * TriggerUpcomingDatetimeNotificationsMock
 * Mock for TriggerUpcomingDatetimeNotifications
 *
 * @package EventEspresso\AutomateUpcomingEventNotificationsTests
 * @subpackage \mocks
 * @author  Darren Ethier
 * @since   1.0.0
 */
class TriggerUpcomingDatetimeNotificationsMock extends TriggerUpcomingDatetimeNotifications
{
    public function process($data)
    {
        parent::process($data);
    }


    public function getData()
    {
        return parent::getData();
    }


    /**j
     * Override default triggerMessages because we don't need to actually queue up and send these.
     * That's covered by other tests.
     *
     * @param $data
     * @param $message_type
     */
    public function triggerMessages($data, $message_type)
    {
        return;
    }


    /**
     * Return the value of the global_message_template_group protected property
     * return EE_Message_Template_Group
     */
    public function globalMessageTemplateGroup()
    {
        return $this->global_message_template_group;
    }


    /**
     * Returns custom message template groups.
     * @return EE_Message_Template_Group[]
     */
    public function messageTemplateGroups()
    {
        return $this->message_template_groups;
    }
}