<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\core\tasks\TriggerUpcomingEventNotifications;
use EE_Message_Template_Group;

/**
 * TriggerUpcomingEventNotificationsMock
 * Mock for TriggerUpcomingEventNotifications
 *
 * @package EventEspresso\AutomateUpcomingEventNotificationsTests
 * @subpackage \mocks
 * @author  Darren Ethier
 * @since   1.0.0
 */
class TriggerUpcomingEventNotificationsMock extends TriggerUpcomingEventNotifications
{

    /**
     * Used for keeping track of messages triggered. It's reset to an empty array on every call to process.
     * Used to simulate what actually got sent on to the messages module.
     * @var array
     */
    public $messages_triggered = array();

    public function process($data)
    {
        $this->messages_triggered = array();
        parent::process($data);
    }


    public function getData()
    {
        return parent::getData();
    }


    /**
     * Override default triggerMessages because we don't need to actually queue up and send these.
     * That's covered by other tests.  However we use this to track what data got sent in and use that to test
     * expectations for what _should_ have been sent to the messages module.
     *
     * @param $data
     * @param $message_type
     */
    public function triggerMessages($data, $message_type)
    {
        $this->messages_triggered = array_merge($this->messages_triggered, $data);
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