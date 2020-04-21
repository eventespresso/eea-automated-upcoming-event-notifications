<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidIdentifierException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use EventEspresso\core\services\commands\CommandInterface;
use EEM_Registration;
use EEM_Event;
use EED_Automated_Upcoming_Event_Notification_Messages;
use EE_Message_Template_Group;
use EEM_Message_Template_Group;
use EE_Error;
use EventEspresso\core\services\commands\CompositeCommandHandler;
use EventEspresso\core\services\loaders\LoaderFactory;
use InvalidArgumentException;
use ReflectionException;

/**
 * UpcomingNotificationsCommandHandler
 * Abstract class for all notifications command handlers.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
 * @author  Darren Ethier
 * @since   1.0.0
 */
abstract class UpcomingNotificationsCommandHandler extends CompositeCommandHandler
{

    /**
     * @var EEM_Registration
     */
    protected $registration_model;


    /**
     * @var SplitRegistrationDataRecordForBatches
     */
    protected $split_data_service;


    /**
     * This will hold the set cron schedule frequency buffer in seconds.  Used by the queries involving threshold range.
     *
     * @var int
     */
    protected $cron_frequency_buffer;


    /**
     * UpcomingNotificationsCommandHandler constructor.
     *
     * @param CommandBusInterface                   $command_bus
     * @param CommandFactoryInterface               $command_factory
     * @param EEM_Registration                      $registration_model
     * @param SplitRegistrationDataRecordForBatches $split_data_service
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public function __construct(
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory,
        EEM_Registration $registration_model,
        SplitRegistrationDataRecordForBatches $split_data_service
    ) {
        $this->registration_model = $registration_model;
        $this->split_data_service = $split_data_service;
        parent::__construct($command_bus, $command_factory);
        $this->setCronFrequencyBuffer();
    }


    /**
     * @param UpcomingNotificationsCommand|CommandInterface $command
     * @return bool
     * @throws InvalidIdentifierException
     * @throws EE_Error
     * @throws InvalidArgumentException
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof UpcomingNotificationsCommand) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__(
                        'The %1$s is expected to receive an instance of %2$s, however an instance of %3$s received instead.',
                        'event_espresso'
                    ),
                    'UpcomingNotificationsCommandHandler',
                    'UpcomingNotificationsCommand',
                    get_class($command)
                )
            );
        }
        $data = $this->getData($command->getMessageTemplateGroups());
        if ($this->shouldBatch($data)) {
            $this->processBatches($data);
        } else {
            $this->process($data);
        }
        return true;
    }


    /**
     * This should handle setting up the data that would be sent into the process method.
     * The expectation is that all registrations in an event that belong to the trigger threshold for ANY datetime in
     * the event are returned.
     *
     * @param EEM_Message_Template_Group[] $message_template_groups
     * @return array
     * @throws InvalidIdentifierException
     * @throws EE_Error
     */
    protected function getData(array $message_template_groups)
    {
        $data = array();
        if (! empty($message_template_groups)) {
            $global_group = null;
            $items_to_exclude = array();
            /** @var EE_Message_Template_Group $message_template_group */
            foreach ($message_template_groups as $message_template_group) {
                // if this is a global group then assign to global group property and continue (will be used later)
                if ($message_template_group->is_global()) {
                    $global_group = $message_template_group;
                    continue;
                }
                $settings = new SchedulingSettings($message_template_group);
                $active_contexts = $settings->allActiveContexts();
                if (count($active_contexts) < 1) {
                    continue;
                }
                foreach ($active_contexts as $context) {
                    $items_to_exclude[ $context ] = isset(
                        $items_to_exclude[ $context ]
                    )
                        ? $items_to_exclude[ $context ]
                        : $this->itemsToExclude($context);
                    $retrieved_data = $this->getDataForCustomMessageTemplateGroup(
                        $settings,
                        $context,
                        $items_to_exclude[ $context ]
                    );
                    $data = $this->combineDataByGroupAndContext(
                        $message_template_group,
                        $context,
                        $data,
                        $retrieved_data
                    );
                }
            }
            if ($global_group instanceof EE_Message_Template_Group) {
                $settings = new SchedulingSettings($global_group);
                $active_contexts = $settings->allActiveContexts();
                if (count($active_contexts) > 0) {
                    foreach ($active_contexts as $context) {
                        $items_to_exclude[ $context ] = isset(
                            $items_to_exclude[ $context ]
                        )
                            ? $items_to_exclude[ $context ]
                            : $this->itemsToExclude($context);
                        $retrieved_data = $this->getDataForGlobalMessageTemplateGroup(
                            $settings,
                            $context,
                            $data,
                            $items_to_exclude[ $context ]
                        );
                        $data = $this->combineDataByGroupAndContext(
                            $global_group,
                            $context,
                            $data,
                            $retrieved_data
                        );
                    }
                }
            }
        }
        return $data;
    }


    /**
     * This takes care of triggering the actual messages
     *
     * @param array  $data
     * @param string $message_type
     * @param string $context
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function triggerMessages(array $data, $message_type, $context)
    {
        /**
         * This filter allows client code to handle the actual sending of messages differently if desired
         */
        if (apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_Domain_Services_Commands_UpcomingNotificationsCommandHandler__triggerMessages__do_default_trigger',
            true,
            $data,
            $message_type,
            $context
        )) {
            EED_Automated_Upcoming_Event_Notification_Messages::prep_and_queue_messages(
                $message_type,
                $data,
                $context
            );
        }
    }


    /**
     * Receives an array of EE_BaseClass Items and sends them to the correct command handler for the given $model_name.
     *
     * @param array $arguments The arguments sent to the processing command.
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected function setItemsProcessed(array $arguments)
    {
        if ($arguments) {
            $this->commandBus()->execute(
                $this->commandFactory()->getNew(
                    'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\ItemsNotifiedCommand',
                    $arguments
                )
            );
        }
    }


    /**
     * Extracts the global group from an array of message template groups if the array has a global group.
     *
     * @param array $message_template_groups
     * @return EE_Message_Template_Group
     */
    protected function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        $global_groups = array_filter(
            $message_template_groups,
            function (EE_Message_Template_Group $message_template_group) {
                return $message_template_group->is_global();
            }
        );
        // there should only be one global group, so we only handle one.
        return $global_groups ? reset($global_groups) : null;
    }


    /**
     * Returns filtered array of Event Statuses to include in the query arguments for getting registrations to notify.
     *
     * @return array
     */
    protected function eventStatusForRegistrationsQuery()
    {
        return (array) apply_filters(
            'FHEE__EventEspresso_AutomatedUpcomingEventNotifications_domain_services_commands_message__eventStatusForRegistrationsQuery',
            array('publish', EEM_Event::sold_out)
        );
    }


    /**
     * The threshold for upcoming notifications is currently in intervals of days.  This means that the accuracy of the
     * time will be down to the day (not the hour or the minute).
     *
     * @return int
     */
    protected function getStartTimeForQuery()
    {
        return time();
    }


    /**
     * This returns the end time for the "upcoming" queries.  It's set to:
     * - the start time for the query
     * - + the current threshold (which is an integer) times a day in seconds.
     * - + the cron frequency buffer (which is a filtered buffer allowing for wp-cron impreciseness)
     *
     * @param SchedulingSettings $settings
     * @param string             $context
     * @return int
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getEndTimeForQuery(SchedulingSettings $settings, $context)
    {
        return $this->getStartTimeForQuery()
               + (DAY_IN_SECONDS * $settings->currentThreshold($context))
               + $this->cron_frequency_buffer;
    }


    /**
     * Return the correct notification meta key for items that have already been notified for the given context.
     *
     * @param string $context
     * @return string
     */
    protected function getNotificationMetaKeyForContext($context)
    {
        return $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
    }


    /**
     * Sets the cron_frequency property that is used in queries
     *
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    private function setCronFrequencyBuffer()
    {
        // Even though $this->commandBus->getCommandHandlerManager() has an instance of the Loader cached in a property
        // it's not accessible, once/if that changes then I can use it instead of the LoaderFactory.
        /** @var Scheduler $scheduler */
        $scheduler = LoaderFactory::getLoader()->getShared(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler'
        );
        $registered_schedules = wp_get_schedules();
        $registered_cron_frequency = $scheduler->getCronFrequency();
        $this->cron_frequency_buffer = isset(
            $registered_schedules[ $registered_cron_frequency ]['interval']
        )
            ? $registered_schedules[ $registered_cron_frequency ]['interval']
            : HOUR_IN_SECONDS * 3;
        // let's add a filterable buffer (why? Because wp-cron is imprecise and won't ALWAYS fire on the set interval).
        $this->cron_frequency_buffer += $this->getCronFrequencyBuffer();
    }


    /**
     * WordPress Cron (wp-cron) is imprecise.  We cannot rely on the events being processed exactly on the interval.
     * This buffer (filterable) allows for extending the query range beyond the next cron scheduled event to cover
     * impreciseness of the schedule.
     *
     * @return int  number of seconds for buffer.
     */
    private function getCronFrequencyBuffer()
    {
        return (int) apply_filters(
            'FHEE__EventEspresso_AutomatedUpcomingEventNotifications_domain_services_commands_message_UpcomingNotificationsCommandHandler__getCronFrequencyBuffer',
            MINUTE_IN_SECONDS * 30
        );
    }


    /**
     * This receives the results from a registration query and adds the registration_ids as the keys for each record.
     *
     * @param array $registration_query_results
     * @return array
     */
    protected function setKeysToRegistrationIds(array $registration_query_results)
    {
        $final_result = array();
        foreach ($registration_query_results as $registration_query_result) {
            if (isset($registration_query_result['REG_ID'])) {
                // set all values to ints
                $registration_query_result = array_map(
                    // using (int) because its significantly faster than intval.
                    function ($value) {
                        return (int) $value;
                    },
                    $registration_query_result
                );
                $final_result[ $registration_query_result['REG_ID'] ] = $registration_query_result;
            }
        }
        return $final_result;
    }


    /**
     * Returns the count at which batching is triggered for the notifications.
     *
     * @return int
     */
    protected function getRegistrationBatchThreshold()
    {
        return apply_filters(
            'FHEE__EventEspresso_AutomatedUpcomingEventNotifications_domain_services_commands_message_UpcomingNotificationsCommandHandler__getRegistrationBatchThreshold',
            150
        );
    }


    /**
     * The purpose for this method is to get the list of registrations that have already been notified.
     *
     * @param string $context The context we're getting the notified registrations for.
     * @return array  of IDs for the primary model associated with this handler (eg events or datetimes)
     */
    abstract protected function itemsToExclude($context);


    /**
     * This retrieves the data for the given SchedulingSettings
     *
     * @param SchedulingSettings $scheduling_settings
     * @param string             $context What context this is for.
     * @param array              $items_to_exclude
     * @return array
     */
    abstract protected function getDataForCustomMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $items_to_exclude
    );


    /**
     * This retrieves the data for the global message template group (if present).
     *
     * @param SchedulingSettings $scheduling_settings This should contain a global EE_Message_Template_Group object.
     * @param string             $context
     * @param array              $data
     * @param array              $items_to_exclude
     * @return array
     */
    abstract protected function getDataForGlobalMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $data,
        array $items_to_exclude
    );


    /**
     * @param EE_Message_Template_Group $message_template_group
     * @param string                    $context       The context for the data
     * @param array                     $data          The data to be combined with
     * @param array                     $incoming_data The incoming data for combining
     * @return array
     */
    abstract protected function combineDataByGroupAndContext(
        EE_Message_Template_Group $message_template_group,
        $context,
        array $data,
        array $incoming_data
    );


    /**
     * Determines whether the number of registrations within the given data warrants processing these as batches.
     * "Batching" in this context is simply ensuring that the messages queued up for generation have a limited number of
     * registrations attached to them so that there's less risk of a server timing out while generating the messages.
     *
     * @param array $data
     * @return bool
     */
    abstract protected function shouldBatch($data);


    /**
     * This method takes care of dividing up the data into appropriate batches and processing each batch.
     *
     * @param array $data
     */
    abstract protected function processBatches($data);


    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     */
    abstract protected function process(array $data);
}
