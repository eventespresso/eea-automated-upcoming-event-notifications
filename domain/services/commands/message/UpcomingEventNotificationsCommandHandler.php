<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use const ARRAY_A;
use EEM_Event;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EEM_Registration;
use EE_Registration;
use EE_Base_Class;
use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingEventNotificationsCommandHandler
 * CommandHandler for UpcomingEventNotificationsCommand
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\commands\message
 * @author     Darren Ethier
 * @since      1.0.0
 */
class UpcomingEventNotificationsCommandHandler extends UpcomingNotificationsCommandHandler
{


    /**
     * @var EEM_Event
     */
    protected $event_model;


    /**
     * UpcomingEventNotificationsCommandHandler constructor.
     *
     * @param CommandBusInterface     $command_bus
     * @param CommandFactoryInterface $command_factory
     * @param EEM_Registration        $registration_model
     * @param EEM_Event               $event_model
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public function __construct(
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory,
        EEM_Registration $registration_model,
        EEM_Event $event_model
    ) {
        parent::__construct($command_bus, $command_factory, $registration_model);
        $this->event_model = $event_model;
    }


    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    protected function process(array $data)
    {
        //initial verification
        if (empty($data)) {
            return;
        }

        //loop through each Message Template Group and it queue up its registrations for generation.
        $event_ids = array();
        /**
         * @var int $message_template_group_id
         * @var EE_Registration[] $context_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_and_registration_data) {
            /**
             * @var string $context
             * @var EE_Registration[] $registrations
             */
            foreach ($context_and_registration_data as $context => $registration_data) {
                $registration_ids = array_keys($registration_data);
                //collect event-ids for the registrations for marking as notified for this context.
                $event_ids = $this->aggregateEventsForContext($registration_data, $event_ids, $context);
                $this->triggerMessages($registration_ids, Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT, $context);
            }
        }


        //k now let's record all the events notified for each context.
        foreach ($event_ids as $context => $event_ids_for_context) {
            $this->setItemsProcessed(
                array($this->event_model, $event_ids_for_context, $context)
            );
        }
    }


    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $registrations_to_exclude_where_query
     * @return array An array of data for processing.
     * @throws EE_Error
     */
    protected function getDataForCustomMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $registrations_to_exclude_where_query
    ) {
        $registrations = $this->getRegistrationsForMessageTemplateGroup(
            $scheduling_settings,
            $context,
            $registrations_to_exclude_where_query
        );
        return $registrations ? $registrations : array();
    }


    /**
     * @param SchedulingSettings $settings
     * @param string             $context
     * @param array              $additional_where_parameters
     * @return EE_Base_Class[]|EE_Registration[]
     * @throws EE_Error
     */
    protected function getRegistrationsForMessageTemplateGroup(
        SchedulingSettings $settings,
        $context,
        array $additional_where_parameters = array()
    ) {
        $where = array(
            'Event.status'                 => array('IN', $this->eventStatusForRegistrationsQuery()),
            'Event.Datetime.DTT_EVT_start' => array(
                'BETWEEN',
                array(
                    $this->getStartTimeForQuery(),
                    $this->getStartTimeForQuery()
                    + (DAY_IN_SECONDS * $settings->currentThreshold($context))
                    + $this->cron_frequency_buffer,
                ),
            ),
            'STS_ID'                       => EEM_Registration::status_id_approved,
            'REG_deleted'                  => 0,
        );

        if ($additional_where_parameters) {
            $where = array_merge($where, $additional_where_parameters);
        }
        if ($settings->getMessageTemplateGroup()->is_global()) {
            $where['OR*global_conditions'] = array(
                'Event.Message_Template_Group.GRP_ID'      => $settings->getMessageTemplateGroup()->ID(),
                'Event.Message_Template_Group.GRP_ID*null' => array('IS NULL'),
            );
        } else {
            $where['Event.Message_Template_Group.GRP_ID'] = $settings->getMessageTemplateGroup()->ID();
        }
        return $this->setKeysToRegistrationIds(
            $this->registration_model->get_all_wpdb_results(
                array($where, 'group_by' => 'REG_ID'),
                ARRAY_A,
                array(
                    'REG_ID' => array('REG_ID', '%d'),
                    'ATT_ID' => array('ATT_ID', '%d'),
                    'EVT_ID' => array('Registration.EVT_ID', '%d')
                )
            )
        );
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $data
     * @param array                                          $registrations_to_exclude_where_query
     * @return array
     * @throws EE_Error
     */
    protected function getDataForGlobalMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $data,
        array $registrations_to_exclude_where_query
    ) {
        if (! $scheduling_settings->getMessageTemplateGroup()->is_global()) {
            return array();
        }

        //extract the ids of registrations already in the data array.
        $additional_where_conditions = array();
        $registration_ids = isset($data[$context])
            ? array_keys($data[$context])
            : array();
        if ($registration_ids) {
            $additional_where_conditions['REG_ID'] = array('NOT_IN', $registration_ids);
        }
        $additional_where_conditions = array_merge(
            $additional_where_conditions,
            $registrations_to_exclude_where_query
        );
        $registrations = $this->getRegistrationsForMessageTemplateGroup(
            $scheduling_settings,
            $context,
            $additional_where_conditions
        );
        return $registrations ? $registrations : array();
    }

    /**
     * Combines data for this handler.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param string                    $context       The context for the data
     * @param array                     $data          The data to be aggregated
     * @param array                     $registrations results from the query where the keys are the registration_id
     *                                                 and the values are an an associative array for the columns
     *                                                 'ATT_ID', 'REG_ID', and 'EVT_ID';
     * @return array
     * @throws EE_Error
     */
    protected function combineDataByGroupAndContext(
        EE_Message_Template_Group $message_template_group,
        $context,
        array $data,
        array $registrations
    ) {
        //here the incoming data is an array of registrations.
        foreach ($registrations as $registration_id => $registration_query_results_record) {
            $data[$message_template_group->ID()][$context][$registration_id] = $registration_query_results_record;
        }
        return $data;
    }


    /**
     * The purpose for this method is to get the where condition for excluding registrations that have already been
     * notified.
     * For this command handler we need to get all the events that have been notified and then use those ids for the
     * where query that will then be used in the eventual registrations query.
     *
     * @param string $context The context we're getting the notified registrations for.
     * @return array The array should be in the format used for EE model where conditions.  Eg.
     *                        array('EVT_ID' => array( 'NOT IN', array(1,2,3))
     * @throws EE_Error
     */
    protected function registrationsToExcludeWhereQueryConditions($context)
    {
        $meta_key = $this->getNotificationMetaKeyForContext($context);
        $where = array(
            'Datetime.DTT_EVT_start' => array('>', time()),
            'Extra_Meta.EXM_key'           => $meta_key,
        );
        $event_ids_notified = $this->event_model->get_col(array($where));
        return $event_ids_notified
            ? array(
                'EVT_ID*already_notified' => array('NOT IN', $event_ids_notified),
            )
            : array();
    }


    /**
     * Retrieves EE_Event objects that haven't already been set on the $events variable for all the registrations sent
     * in for the given context.
     *
     * @param $registration_result_records $registrations  In the format
     *                                     array(array('REG_ID' => 'x', 'ATT_ID' => 'x', 'EVT_ID' => 'x'));
     * @param array             $incoming_event_ids
     * @param string            $context
     * @return array
     * @throws EE_Error
     */
    protected function aggregateEventsForContext(
        array $registration_result_records,
        array $incoming_event_ids,
        $context
    ) {
        foreach ($registration_result_records as $registration_result_record) {
            $registration_event_id = $registration_result_record['EVT_ID'];
            $incoming_event_ids[$context][$registration_event_id] = $registration_event_id;
        }
        return $incoming_event_ids;
    }
}
