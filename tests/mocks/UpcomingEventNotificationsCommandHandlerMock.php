<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EE_Error;
use EEM_Event;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingEventNotificationsCommandHandler;
use EEM_Registration;
use EE_Registry;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBus;
use EventEspresso\core\services\commands\CommandFactory;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;
use ReflectionException;

/** @noinspection LongInheritanceChainInspection */

/**
 * UpcomingEventNotificationsCommandHandlerMock
 *
 *
 * @package EventEspresso\AutomateUpcomingEventNotificationsTests\mocks
 * @author  Darren Ethier
 * @since   1.0.0
 */
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


    /**
     * UpcomingEventNotificationsCommandHandlerMock constructor.
     *
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct(
            LoaderFactory::getLoader()->getShared(CommandBus::class),
            LoaderFactory::getLoader()->getShared(CommandFactory::class),
            EEM_Registration::instance(),
            EEM_Event::instance(),
            new SplitRegistrationDataRecordForBatches()
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
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
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
