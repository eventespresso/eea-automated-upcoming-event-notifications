<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EEM_Event;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingEventNotificationsCommandHandler;
use EEM_Registration;
use EE_Registry;

class UpcomingEventNotificationsCommandHandlerMock extends UpcomingEventNotificationsCommandHandler
{
    /**
     * Used for keeping track of messages triggered. It's reset to an empty array on every call to process.
     * Used to simulate what actually got sent on to the messages module.
     * @var array
     */
    public $messages_triggered = array();


    /**
     * Used by test cases to flag whether to trigger actual message sends.
     * @var bool
     */
    private $trigger_actual_messages = false;


    public function __construct()
    {
        parent::__construct(
            EE_Registry::instance()->create(
                'EventEspresso\core\services\commands\CommandBus'
            ),
            EE_Registry::instance()->create(
                'EventEspresso\core\services\commands\CommandFactory'
            ),
            EEM_Registration::instance(),
            EEM_Event::instance()
        );
    }

    public function process(array $data)
    {
        $this->messages_triggered = array();
        parent::process($data);
    }


    /**
     * Used by testcases to flag whether actual messages should be sent or not.
     * @param bool $trigger_actual_messages
     */
    public function setTriggerActualMessages($trigger_actual_messages = false)
    {
        $this->trigger_actual_messages = $trigger_actual_messages;
    }


    public function getData(array $message_template_groups)
    {
        return parent::getData($message_template_groups);
    }


    /**
     * Override default triggerMessages because we don't need to actually queue up and send these.
     * That's covered by other tests.  However we use this to track what data got sent in and use that to test
     * expectations for what _should_ have been sent to the messages module.
     *
     * @param array  $data
     * @param string $message_type
     * @param        $context
     */
    public function triggerMessages(array $data, $message_type, $context)
    {
        $this->messages_triggered = array_merge($this->messages_triggered, $data);
        if ($this->trigger_actual_messages) {
            parent::triggerMessages($data, $message_type, $context);
        }
    }


    public function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        return parent::extractGlobalMessageTemplateGroup($message_template_groups);
    }


    public function aggregateEventsForContext(array $registrations, array $incoming_events, $context)
    {
        return parent::aggregateEventsForContext($registrations, $incoming_events, $context);
    }
}
