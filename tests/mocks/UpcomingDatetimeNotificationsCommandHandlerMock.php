<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingDatetimeNotificationsCommandHandler;
use EEM_Registration;
use EEM_Datetime;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBus;
use EventEspresso\core\services\commands\CommandFactory;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;

/** @noinspection LongInheritanceChainInspection */

/**
 * UpcomingDatetimeNotificationsCommandHandlerMock
 *
 *
 * @package EventEspresso\AutomateUpcomingEventNotificationsTests\mocks
 * @author  Darren Ethier
 * @since   1.0.0
 */
class UpcomingDatetimeNotificationsCommandHandlerMock extends UpcomingDatetimeNotificationsCommandHandler
{
    /**
     * UpcomingDatetimeNotificationsCommandHandlerMock constructor.
     *
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(
            LoaderFactory::getLoader()->getShared(CommandBus::class),
            LoaderFactory::getLoader()->getShared(CommandFactory::class),
            EEM_Registration::instance(),
            EEM_Datetime::instance(),
            new SplitRegistrationDataRecordForBatches()
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
    }


    public function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        return parent::extractGlobalMessageTemplateGroup($message_template_groups);
    }

    /**
     * Wrappers of the protected function for getting the meta's name
     * @since $VID:$
     * @param string $context
     * @return mixed|string
     */
    public function getNotificationMetaKeyForContext($context){
        return parent::getNotificationMetaKeyForContext($context);
    }
}