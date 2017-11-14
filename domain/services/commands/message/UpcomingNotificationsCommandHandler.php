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

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

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
            $registrations_to_exclude_where_query = array();
            /** @var EE_Message_Template_Group $message_template_group */
            foreach ($message_template_groups as $message_template_group) {
                //if this is a global group then assign to global group property and continue (will be used later)
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
                    $registrations_to_exclude_where_query[$context] = isset(
                        $registrations_to_exclude_where_query[$context]
                    )
                        ? $registrations_to_exclude_where_query[$context]
                        : $this->registrationsToExcludeWhereQueryConditions($context);
                    $retrieved_data = $this->getDataForCustomMessageTemplateGroup(
                        $settings,
                        $context,
                        $registrations_to_exclude_where_query[$context]
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
                        $registrations_to_exclude_where_query[$context] = isset(
                            $registrations_to_exclude_where_query[$context]
                        )
                           ? $registrations_to_exclude_where_query[$context]
                           : $this->registrationsToExcludeWhereQueryConditions($context);
                        $retrieved_data = $this->getDataForGlobalMessageTemplateGroup(
                            $settings,
                            $context,
                            $data,
                            $registrations_to_exclude_where_query[$context]
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
     * @param array           $arguments                          The arguments sent to the processing command.
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
     * @throws EE_Error
     */
    protected function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        $global_groups = array_filter(
            $message_template_groups,
            function (EE_Message_Template_Group $message_template_group) {
                return $message_template_group->is_global();
            }
        );
        //there should only be one global group, so we only handle one.
        return $global_groups ? reset($global_groups) : null;
    }


    /**
     * Returns filtered array of Event Statuses to include in the query arguments for getting registrations to notify.
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
     * time will be down to the day (not the hour or the minute).  Since our scheduled cron fires daily at midnight, if
     * the threshold is set to one day before the day of the event, then anything between 00:00 and 23:59 of the day
     * the cron fired is NOT one day before the event but UNDER one day.  So for accuracy at this interval, the start
     * time for the query should be one day from the time the cron job is triggered. This means then that if the
     * threshold is set to one day, and the time the scheduled cron fires is September 21, 00:00:00, we want to query
     * for start datetimes between September 22, 00:00:00 and September 22, 23:59:59 (we'll actually do the query for to
     * include that last minute so `September 23, 00:00:00 for simplicity).
     *
     * @return int
     */
    protected function getStartTimeForQuery()
    {
        return time() + DAY_IN_SECONDS;
    }


    /**
     * Return the correct notification meta key for items that have already been notified for the given context.
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
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    private function setCronFrequencyBuffer()
    {
        //Even though $this->commandBus->getCommandHandlerManager() has an instance of the Loader cached in a property
        //it's not accessible, once/if that changes then I can use it instead of the LoaderFactory.
        /** @var Scheduler $scheduler */
        $scheduler = LoaderFactory::getLoader()->getShared(
            'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks\Scheduler'
        );
        $registered_schedules = wp_get_schedules();
        $registered_cron_frequency = $scheduler->getCronFrequency();
        $this->cron_frequency_buffer = isset(
            $registered_schedules[$registered_cron_frequency],
            $registered_schedules[$registered_cron_frequency]['interval']
        )
            ? $registered_schedules[$registered_cron_frequency]['interval']
            : HOUR_IN_SECONDS * 3;
        //let's add a filterable buffer (why? Because wp-cron is imprecise and won't ALWAYS fire on the set interval).
        $this->cron_frequency_buffer += $this->getCronFrequencyBuffer();
    }


    /**
     * WordPress Cron (wp-cron) is imprecise.  We cannot rely on the events being processed exactly on the interval.
     * This buffer (filterable) allows for extending the query range beyond the next cron scheduled event to cover
     * impreciseness of the schedule.
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
                //set all values to ints
                $registration_query_result = array_map(
                //using (int) because its significantly faster than intval.
                    function ($value) {
                        return (int) $value;
                    },
                    $registration_query_result
                );
                $final_result[$registration_query_result['REG_ID']] = $registration_query_result;
            }
        }
        return $final_result;
    }


    /**
     * Returns the count at which batching is triggered for the notifications.
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
     * The purpose for this method is to get the where condition for excluding registrations that have already been
     * notified.
     *
     * @param string $context  The context we're getting the notified registrations for.
     * @return array  The array should be in the format used for EE model where conditions.  Eg.
     *                array('EVT_ID' => array( 'NOT IN', array(1,2,3))
     */
    abstract protected function registrationsToExcludeWhereQueryConditions($context);


    /**
     * This retrieves the data for the given SchedulingSettings
     *
     * @param SchedulingSettings $scheduling_settings
     * @param string             $context   What context this is for.
     * @param array              $registrations_to_exclude_where_query
     * @return
     */
    abstract protected function getDataForCustomMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $registrations_to_exclude_where_query
    );


    /**
     * This retrieves the data for the global message template group (if present).
     *
     * @param SchedulingSettings $scheduling_settings This should contain a global EE_Message_Template_Group object.
     * @param string             $context
     * @param array              $data
     * @param array              $registrations_to_exclude_where_query
     * @return array
     */
    abstract protected function getDataForGlobalMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $data,
        array $registrations_to_exclude_where_query
    );


    /**
     * @param EE_Message_Template_Group $message_template_group
     * @param string                    $context The context for the data
     * @param array                     $data    The data to be combined with
     * @param array                     $incoming_data  The incoming data for combining
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
     * @param array $data
     * @return bool
     */
    abstract protected function shouldBatch($data);




    /**
     * This method takes care of dividing up the data into appropriate batches and processing each batch.
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
