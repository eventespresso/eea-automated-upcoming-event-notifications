<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\UpcomingEventNotificationsCommandHandler;
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

    public function __construct()
    {
        parent::__construct(
            EEM_Registration::instance(),
            EE_Registry::instance()
        );
        $this->setCommandBus(EE_Registry::instance()->create('EventEspresso\core\services\commands\CommandBus'));
    }

    public function process(array $data)
    {
        $this->messages_triggered = array();
        parent::process($data);
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
     * @param $data
     * @param $message_type
     */
    public function triggerMessages(array $data, $message_type)
    {
        $this->messages_triggered = array_merge($this->messages_triggered, $data);
    }


    public function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        return parent::extractGlobalMessageTemplateGroup($message_template_groups);
    }
}
