<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingDatetimeNotificationsCommandHandler;
use EE_Registry;
use EEM_Registration;
use EEM_Datetime;

class UpcomingDatetimeNotificationsCommandHandlerMock extends UpcomingDatetimeNotificationsCommandHandler
{
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
            EEM_Datetime::instance()
        );
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
     * @param array  $data
     * @param string $message_type
     * @param        $context
     */
    public function triggerMessages(array $data, $message_type, $context)
    {
        return;
    }


    public function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        return parent::extractGlobalMessageTemplateGroup($message_template_groups);
    }
}