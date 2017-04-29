<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\UpcomingDatetimeNotificationsCommandHandler;
use EE_Registry;
use EEM_Registration;
use EEM_Datetime;

class UpcomingDatetimeNotificationsCommandHandlerMock extends UpcomingDatetimeNotificationsCommandHandler
{
    public function __construct()
    {
        parent::__construct(
            EEM_Registration::instance(),
            EEM_Datetime::instance(),
            EE_Registry::instance()
        );
        $this->setCommandBus(EE_Registry::instance()->create('EventEspresso\core\services\commands\CommandBus'));
    }

    public function process(array $data)
    {
        parent::process($data);
    }


    public function getData(array $message_template_groups)
    {
        return parent::getData($message_template_groups);
    }


    /**j
     * Override default triggerMessages because we don't need to actually queue up and send these.
     * That's covered by other tests.
     *
     * @param $data
     * @param $message_type
     */
    public function triggerMessages(array $data, $message_type)
    {
        return;
    }


    public function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        return parent::extractGlobalMessageTemplateGroup($message_template_groups);
    }
}