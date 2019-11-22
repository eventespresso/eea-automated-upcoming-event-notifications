<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EE_Datetime_Field;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EEM_Registration;
use EEM_Datetime;
use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use InvalidArgumentException;
use ReflectionException;

/**
 * UpcomingDatetimeNotificationsCommandHandler
 * CommandHandler for UpcomingDatetimeNotificationsCommand
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\commands\message
 * @author     Darren Ethier
 * @since      1.0.0
 */
class UpcomingDatetimeNotificationsCommandHandler extends UpcomingNotificationsCommandHandler
{

    /**
     * @var EEM_Datetime
     */
    protected $datetime_model;


    /**
     * UpcomingDatetimeNotificationsCommandHandler constructor.
     *
     * @param CommandBusInterface                   $command_bus
     * @param CommandFactoryInterface               $command_factory
     * @param EEM_Registration                      $registration_model
     * @param EEM_Datetime                          $datetime_model
     * @param SplitRegistrationDataRecordForBatches $split_data_service
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public function __construct(
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory,
        EEM_Registration $registration_model,
        EEM_Datetime $datetime_model,
        SplitRegistrationDataRecordForBatches $split_data_service
    ) {
        parent::__construct($command_bus, $command_factory, $registration_model, $split_data_service);
        $this->datetime_model = $datetime_model;
    }


    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function process(array $data)
    {
        // initial verification
        if (empty($data)) {
            return;
        }

        // loop through each Message Template Group and it queue up its registrations for generation.
        /**
         * @var int   $message_template_group_id
         * @var array $context_datetime_ids_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_datetime_ids_and_registrations) {
            /**
             * @var string $context
             * @var array  $datetime_ids_and_registrations
             */
            foreach ($context_datetime_ids_and_registrations as $context => $datetime_ids_and_registrations) {
                $datetimes_processed = array();
                foreach ($datetime_ids_and_registrations as $datetime_id => $datetimeid_and_registration_records) {
                    $message_data = array(
                        $datetime_id,
                        array_keys($datetimeid_and_registration_records[1]),
                    );
                    $this->triggerMessages(
                        $message_data,
                        Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME,
                        $context
                    );
                    $datetimes_processed[] = $datetime_id;
                }
                // set the datetimes as having been processed.
                $this->setItemsProcessed(
                    array($this->datetime_model, $datetimes_processed, $context)
                );
            }
        }
    }


    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $items_to_exclude
     * @return array An array of data for processing.
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getDataForCustomMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $items_to_exclude
    ) {
        return $this->getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
            $scheduling_settings,
            $context,
            $items_to_exclude
        );
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $data
     * @param array                                          $items_to_exclude
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getDataForGlobalMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $data,
        array $items_to_exclude
    ) {
        if (! $scheduling_settings->getMessageTemplateGroup()->is_global()) {
            return $data;
        }

        // extract the ids of the datetimes already in the data so we exclude them from the global message template group
        // based query.
        $datetime_ids_from_data = $this->getDateTimeIdsFromData($data, $context);
        // combine the datetimes from data with the items we already know we want to exclude.
        $items_to_exclude = array_merge($items_to_exclude, $datetime_ids_from_data);
        return $this->getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
            $scheduling_settings,
            $context,
            $items_to_exclude
        );
    }


    /**
     * Get ids of datetimes from passed in array.
     *
     * @param array  $data
     * @param string $context
     * @return array
     */
    protected function getDateTimeIdsFromData(array $data, $context)
    {
        $datetime_ids = array();
        /**
         * @var int   $group_id
         * @var array $datetime_records
         */
        foreach ($data as $group_id => $datetime_records) {
            if (isset($datetime_records[ $context ])) {
                $datetime_ids = array_keys($datetime_records[ $context ]);
            }
        }
        return array_unique($datetime_ids);
    }


    /**
     * Build a query to get datetimes and registrations for the given message template group.
     *
     * @param SchedulingSettings $settings
     * @param string             $context
     * @param array              $items_to_exclude
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
        SchedulingSettings $settings,
        $context,
        array $items_to_exclude = array()
    ) {
        $data = array();
        $datetime_ids = $this->getDatetimesForMessageTemplateGroupAndContext(
            $settings,
            $context,
            $items_to_exclude
        );
        if (! $datetime_ids) {
            return $data;
        }

        foreach ($datetime_ids as $datetime_id) {
            $datetime_id = (int) $datetime_id;
            $registration_records = $this->getRegistrationsForDatetime(
                $datetime_id
            );
            if (! $registration_records) {
                continue;
            }
            $data[ $datetime_id ] = array($datetime_id, $registration_records);
        }
        return $data;
    }


    /**
     * Get Datetimes from the given message template group
     *
     * @param SchedulingSettings $settings
     * @param string             $context
     * @param array              $additional_where_parameters
     * @return array (an array of datetime ids)
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getDatetimesForMessageTemplateGroupAndContext(
        SchedulingSettings $settings,
        $context,
        array $datetimes_to_exclude = array()
    ) {
        global $wpdb;
        $event_statuses_sql = implode(',', array_map(function ($input) {
            return "'$input'";
        }, $this->eventStatusForRegistrationsQuery()));
        $query_with_placeholders = "
        SELECT 
          Datetime.DTT_ID  
        FROM  
          {$wpdb->prefix}esp_datetime AS Datetime  
          LEFT JOIN {$wpdb->prefix}posts AS Event_CPT ON Event_CPT.ID=Datetime.EVT_ID 
          LEFT JOIN {$wpdb->prefix}esp_event_meta AS Event_Meta ON Event_CPT.ID = Event_Meta.EVT_ID 
          LEFT JOIN (
            SELECT emt.GRP_ID, emt.EVT_ID, mtp.MTP_deleted FROM 
            {$wpdb->prefix}esp_event_message_template AS emt
            INNER JOIN {$wpdb->prefix}esp_message_template_group mtp ON emt.GRP_ID = mtp.GRP_ID AND mtp.MTP_message_type = 'automate_upcoming_datetime'
            ) AS emt_mtp ON Event_CPT.ID = emt_mtp.EVT_ID  
          WHERE 
          Datetime.DTT_deleted = 0 
          AND (Event_CPT.post_type = 'espresso_events') 
          AND  ( (emt_mtp.MTP_deleted = 0) OR emt_mtp.GRP_ID IS NULL) 
          AND Datetime.DTT_EVT_start BETWEEN %s AND %s
          AND Event_CPT.post_status IN ($event_statuses_sql) 
          AND  (emt_mtp.GRP_ID = %d 
        ";
        if ($settings->getMessageTemplateGroup()->is_global()) {
            $query_with_placeholders .= ' OR emt_mtp.GRP_ID IS NULL';
        }
        $query_with_placeholders .= ')';
        if ($datetimes_to_exclude) {
            $query_with_placeholders .= ' AND Datetime.DTT_ID NOT IN (' . implode(',', $datetimes_to_exclude) . ')';
        }
        $query_with_placeholders .= " GROUP BY Datetime.DTT_ID";
        $query = $wpdb->prepare(
            $query_with_placeholders,
            date(EE_Datetime_Field::mysql_timestamp_format, $this->getStartTimeForQuery()),
            date(EE_Datetime_Field::mysql_timestamp_format, $this->getEndTimeForQuery($settings, $context)),
            $settings->getMessageTemplateGroup()->ID()
        );
        return $wpdb->get_col($query);
    }


    /**
     * Get registration results for given message template group and Datetime.
     *
     * @param int $datetime_id
     * @return array    Array of results where keys are registration ID and has the values in the format:
     *                  array( 'REG_ID' => %d, 'ATT_ID' => %d, 'EVT_ID' => %d )
     * @throws EE_Error
     */
    protected function getRegistrationsForDatetime($datetime_id)
    {
        // get registration ids for each datetime and include with the array.
        $where = array(
            'STS_ID'                 => EEM_Registration::status_id_approved,
            'Ticket.Datetime.DTT_ID' => $datetime_id,
            'REG_deleted'            => 0,
        );
        return $this->setKeysToRegistrationIds(
            $this->registration_model->get_all_wpdb_results(
                array($where, 'group_by' => 'REG_ID'),
                ARRAY_A,
                array(
                    'REG_ID' => array('REG_ID', '%d'),
                    'ATT_ID' => array('ATT_ID', '%d'),
                    'EVT_ID' => array('Registration.EVT_ID', '%d'),
                    'TXN_ID' => array('Registration.TXN_ID', '%d'),
                )
            )
        );
    }


    /**
     * The purpose for this method is to get the where condition for excluding registrations that have already been
     * notified.  For this command handler, since notifications are tracked against datetimes, we will get all the
     * datetimes that have been notified and this conditional will be used in the initial query for getting datetimes.
     *
     * @param string $context The context we're getting the notified registrations for.
     * @return array The array should be numeric and contain datetime ID's that have already been notified.
     * @throws EE_Error
     */
    protected function itemsToExclude($context)
    {
        // get all datetimes that have already been notified (greater than now)
        $meta_key = $this->getNotificationMetaKeyForContext($context);
        $where = array(
            'DTT_EVT_start'      => array('>', time()),
            'Extra_Meta.EXM_key' => $meta_key,
        );
        return $this->datetime_model->get_col(array($where));
    }

    /**
     * Combines data for this handler.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param string                    $context The context for the data
     * @param array                     $data    The data to be aggregated
     * @param array                     $datetimes_and_registrations
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function combineDataByGroupAndContext(
        EE_Message_Template_Group $message_template_group,
        $context,
        array $data,
        array $datetimes_and_registrations
    ) {
        // here the incoming data is an array of arrays where the key is datetime_ID and the value is
        // an array where the first value is the datetime_id, and the second value is the registration query results for
        // the registrations attached to that datetime ('ATT_ID', 'EVT_ID', and 'REG_ID' is with each result)
        foreach ($datetimes_and_registrations as $datetime_id => $datetime_id_and_registrations) {
            $datetime_id = (int) $datetime_id;
            $data[ $message_template_group->ID() ][ $context ][ $datetime_id ] = $datetime_id_and_registrations;
        }
        return $data;
    }


    /**
     * Determines whether the number of registrations within the given data warrants processing these as batches.
     * "Batching" in this context is simply ensuring that the messages queued up for generation have a limited number of
     * registrations attached to them so that there's less risk of a server timing out while generating the messages.
     *
     * @param array $data
     * @return bool
     */
    protected function shouldBatch($data)
    {
        /**
         * @var array $context_datetime_ids_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_datetime_ids_and_registrations) {
            /**
             * @var array $datetime_ids_and_registrations
             */
            foreach ($context_datetime_ids_and_registrations as $context => $datetime_ids_and_registrations) {
                foreach ($datetime_ids_and_registrations as $datetime_id => $registration_records) {
                    if (count($registration_records) > $this->getRegistrationBatchThreshold()) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * This method takes care of dividing up the data into appropriate batches and processing each batch.
     * The messages system itself has batching in place for each message queued for generation.  The batching here just
     * ensures that the message system receives a smaller amount of data for each message to be generated.
     *
     * @param array $data
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function processBatches($data)
    {
        $non_batched_items_for_processing = array();
        /**
         * process batches for each context and message template group.
         *
         * @var array $context_datetime_ids_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_datetime_ids_and_registrations) {
            /**
             * @var array $datetime_ids_and_registrations
             */
            foreach ($context_datetime_ids_and_registrations as $context => $datetime_ids_and_registrations) {
                foreach ($datetime_ids_and_registrations as $datetime_id => $datetimeid_and_registration_records) {
                    // popoff $registration records from the second element.
                    $registration_records = $datetimeid_and_registration_records[1];
                    // only batch if necessary.
                    if (count($registration_records) > $this->getRegistrationBatchThreshold()) {
                        $batches = $context === 'admin'
                            ? $this->split_data_service->splitDataByEventId(
                                $registration_records,
                                $this->getRegistrationBatchThreshold()
                            )
                            : $this->split_data_service->splitDataByAttendeeId(
                                $registration_records,
                                $this->getRegistrationBatchThreshold()
                            );
                        foreach ($batches as $batch) {
                            $item_for_processing = array(
                                $message_template_group_id => array(
                                    $context => array(
                                        (int) $datetime_id,
                                        $this->split_data_service->convertStringIndexesToIdFor($batch),
                                    ),
                                ),
                            );
                            $this->process($item_for_processing);
                        }
                        continue;
                    }
                    $non_batched_items_for_processing[ $message_template_group_id ][ $context ][ $datetime_id ]
                        = $registration_records;
                }
            }
        }
        if ($non_batched_items_for_processing) {
            $this->process($non_batched_items_for_processing);
        }
    }
}
