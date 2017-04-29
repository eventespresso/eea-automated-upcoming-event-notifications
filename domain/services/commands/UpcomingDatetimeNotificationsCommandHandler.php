<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\SchedulingSettings;
use EEM_Registration;
use EEM_Datetime;
use EE_Error;
use EE_Message_Template_Group;
use EE_Datetime;
use EE_Registration;
use EE_Registry;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingDatetimeNotificationsCommandHandler
 * CommandHandler for UpcomingDatetimeNotificationsCommand
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\commands
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
     * @param EEM_Registration $registration_model
     * @param EEM_Datetime     $datetime_model
     * @param EE_Registry      $registry
     */
    public function __construct(
        EEM_Registration $registration_model,
        EEM_Datetime $datetime_model,
        EE_Registry $registry
    ) {
        parent::__construct($registration_model, $registry);
        $this->datetime_model = $datetime_model;
    }

    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     * @throws EE_Error
     */
    protected function process(array $data)
    {
        //initial verification
        if (empty($data)) {
            return;
        }

        //loop through each Message Template Group and it queue up its registrations for generation.
        foreach ($data as $message_template_group_id => $datetimes_and_registrations) {
            foreach ($datetimes_and_registrations as $datetime_and_registrations) {
                $this->triggerMessages($datetime_and_registrations, 'automate_upcoming_datetime');
                //extract the datetime so we can use for the processed reference.
                $datetime = isset($datetime_and_registrations[0]) ? $datetime_and_registrations[0] : null;
                if ($datetime instanceof EE_Datetime) {
                    //extract the registrations and mark them as having been notified.  Even though messages will get sent
                    //on a separate request, we don't have access to that so we simply mark them as having been processed.
                    $registrations = isset($datetime_and_registrations[1]) ? $datetime_and_registrations[1] : array();
                    $this->setRegistrationsProcessed($registrations, 'DTT_' . $datetime->ID());
                }
            }
        }
    }


    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @return array An array of data for processing.
     * @throws EE_Error
     */
    protected function getDataForCustomMessageTemplateGroups(array $message_template_groups)
    {
        $data = array();
        foreach ($message_template_groups as $message_template_group) {
            $date_data = $this->getRegistrationsForDatetimeAndMessageTemplateGroup($message_template_group);
            if ($date_data) {
                $data[$message_template_group->ID()] = $date_data;
            }
        }
        return $data;
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @param array                     $data
     * @return array
     * @throws EE_Error
     */
    protected function getDataForGlobalMessageTemplateGroup(array $message_template_groups, array $data)
    {
        $global_message_template_group = $this->extractGlobalMessageTemplateGroup($message_template_groups);
        if (! $global_message_template_group instanceof EE_Message_Template_Group) {
            return $data;
        }

        //extract the ids of the datetimes already in the data so we exclude them from the global message template group
        //based query.
        $datetime_ids                         = $this->getDateTimeIdsFromData($data);
        $additional_datetime_where_conditions = array();
        if ($datetime_ids) {
            $additional_datetime_where_conditions['DTT_ID'] = array('NOT IN', $datetime_ids);
        }
        $date_data = $this->getRegistrationsForDatetimeAndMessageTemplateGroup(
            $global_message_template_group,
            $additional_datetime_where_conditions
        );
        if ($date_data) {
            $data[$global_message_template_group->ID()] = $date_data;
        }
        return $data;
    }


    /**
     * Get ids of datetimes from passed in array.
     *
     * @param array $data
     * @return array
     */
    protected function getDateTimeIdsFromData(array $data)
    {
        $datetime_ids = array();
        foreach ($data as $group_id => $datetime_records) {
            $datetime_ids = array_keys($datetime_records);
        }
        return array_unique($datetime_ids);
    }


    /**
     * Build a query to get datetimes and registrations for the given message template group.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param array                     $datetime_additional_where_conditions
     * @return array
     * @throws EE_Error
     */
    protected function getRegistrationsForDatetimeAndMessageTemplateGroup(
        EE_Message_Template_Group $message_template_group,
        array $datetime_additional_where_conditions = array()
    ) {
        $data = array();
        $settings = new SchedulingSettings($message_template_group);
        //do a fail-safe on whether this message template group is active first.
        if (! $settings->isActive()) {
            return $data;
        }
        $datetimes = $this->getDatetimesForMessageTemplateGroup(
            $settings,
            $message_template_group,
            $datetime_additional_where_conditions
        );
        if (! $datetimes) {
            return $data;
        }
        foreach ($datetimes as $datetime) {
            $registrations = $this->getRegistrationsForDatetime(
                $datetime
            );
            if (! $registrations) {
                continue;
            }
            $data[$datetime->ID()] = array($datetime, $registrations);
        }
        return $data;
    }


    /**
     * Get Datetimes from the given message template group
     *
     * @param SchedulingSettings        $settings
     * @param EE_Message_Template_Group $message_template_group
     * @param array                     $additional_where_parameters
     * @return \EE_Base_Class|EE_Datetime[]
     * @throws EE_Error
     */
    protected function getDatetimesForMessageTemplateGroup(
        SchedulingSettings $settings,
        EE_Message_Template_Group $message_template_group,
        $additional_where_parameters = array()
    ) {
        $where = array(
            'DTT_EVT_start'                       => array(
                'BETWEEN',
                array(
                    time(),
                    time() + (DAY_IN_SECONDS * $settings->currentThreshold()),
                ),
            ),
            'Event.status'                        => 'publish',
        );
        if ($message_template_group->is_global()) {
            $where['OR'] = array(
                'Event.Message_Template_Group.GRP_ID' => $message_template_group->ID(),
                'Event.Message_Template_Group.GRP_ID*null' => array('IS NULL')
            );
        } else {
            $where['Event.Message_Template_Group.GRP_ID'] = $message_template_group->ID();
        }
        if ($additional_where_parameters) {
            $where = array_merge($where, $additional_where_parameters);
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->datetime_model->get_all(array($where));
    }


    /**
     * Get registrations for given message template group and Datetime.

     * @param EE_Datetime               $datetime
     * @return \EE_Base_Class[]|EE_Registration[]
     * @throws EE_Error
     */
    protected function getRegistrationsForDatetime(
        EE_Datetime $datetime
    ) {
        //get registration ids for each datetime and include with the array.
        $where = array(
            'STS_ID'                 => EEM_Registration::status_id_approved,
            'Ticket.Datetime.DTT_ID' => $datetime->ID(),
            'REG_deleted'            => 0,
            'OR'                     => array(
                'Extra_Meta.EXM_key'                 => array('IS NULL'),
                'Extra_Meta.EXM_key*exclude_tracker' => array(
                    '!=',
                    Constants::REGISTRATION_TRACKER_PREFIX . 'DTT_' . $datetime->ID(),
                ),
            ),
        );
        return $this->registration_model->get_all(array($where));
    }
}
