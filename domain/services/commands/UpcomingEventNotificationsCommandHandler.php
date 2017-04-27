<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\SchedulingSettings;
use EEM_Registration;
use EE_Registration;
use EE_Base_Class;
use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingEventNotificationsCommandHandler
 * CommandHandler for UpcomingEventNotificationsCommand
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\commands
 * @author     Darren Ethier
 * @since      1.0.0
 */
class UpcomingEventNotificationsCommandHandler extends UpcomingNotificationsCommandHandler
{

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
        foreach ($data as $message_template_group_id => $registrations) {
            $this->triggerMessages($registrations, 'automate_upcoming_event');
            $this->setRegistrationsProcessed($registrations, 'EVT');
        }
    }

    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * return array An array of data for processing.
     * @throws EE_Error
     */
    protected function getDataForCustomMessageTemplateGroups(array $message_template_groups)
    {
        $data = array();
        foreach ($message_template_groups as $message_template_group) {
            $registrations = $this->getRegistrationsForMessageTemplateGroup($message_template_group);
            if ($registrations) {
                $data = $this->addToDataByGroupAndRegistrations($message_template_group, $registrations, $data);
            }
        }
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @param array                           $data
     * @return array
     * @throws EE_Error
     */
    protected function getDataForGlobalMessageTemplateGroup(array $message_template_groups, array $data)
    {
        $global_message_template_group = $this->extractGlobalMessageTemplateGroup($message_template_groups);
        if (! $global_message_template_group instanceof EE_Message_Template_Group) {
            return $data;
        }

        //extract the ids of registrations already in the data array.
        $registration_ids = array();
        foreach ($data as $message_template_group_registrations) {
            $registration_ids = array_merge(array_keys($message_template_group_registrations), $registration_ids);
        }
        $registration_ids            = array_unique($registration_ids);
        $additional_where_parameters = array();
        if ($registration_ids) {
            $additional_where_parameters['REG_ID'] = array('NOT IN', $registration_ids);
        }

        $registrations = $this->getRegistrationsForMessageTemplateGroup(
            $global_message_template_group,
            $additional_where_parameters
        );

        if ($registrations) {
            $data = $this->addToDataByGroupAndRegistrations($global_message_template_group, $registrations, $data);
        }
        return $data;
    }


    /**
     * @param EE_Message_Template_Group $message_template_group
     * @param array                     $additional_where_parameters
     * @return EE_Registration[]|EE_Base_Class[]
     * @throws EE_Error
     */
    protected function getRegistrationsForMessageTemplateGroup(
        EE_Message_Template_Group $message_template_group,
        $additional_where_parameters = array()
    ) {
        $settings = new SchedulingSettings($message_template_group);
        //fail-safe ... dont' do anything if this group isn't active for automation or if its a global group.
        if (! $settings->isActive() || $message_template_group->is_global()) {
            return array();
        }
        $where = array(
            'Event.status'                        => 'publish',
            'Event.Message_Template_Group.GRP_ID' => $message_template_group->ID(),
            'Event.Datetime.DTT_EVT_start'        => array(
                'BETWEEN',
                array(
                    time(),
                    time() + (DAY_IN_SECONDS * $settings->currentThreshold()),
                ),
            ),
            'STS_ID'                              => EEM_Registration::status_id_approved,
            'REG_deleted'                         => 0,
            'OR'                                  => array(
                'Extra_Meta.EXM_key'                 => array('IS NULL'),
                'Extra_Meta.EXM_key*exclude_tracker' => array('!=', Constants::REGISTRATION_TRACKER_PREFIX . 'EVT'),
            ),
        );
        if ($additional_where_parameters) {
            $where = array_merge($where, $additional_where_parameters);
        }
        return $this->registration_model->get_all(array($where));
    }


    /**
     * This appends to the (existing) data array using the given message template group, registrations and existing data
     * array.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param EE_Registration[]         $registrations
     * @param array                     $data
     * @return array
     * @throws EE_Error
     */
    protected function addToDataByGroupAndRegistrations(
        EE_Message_Template_Group $message_template_group,
        array $registrations,
        array $data
    ) {
        foreach ($registrations as $registration) {
            $data[$message_template_group->ID()][$registration->ID()] = $registration;
        }
        return $data;
    }
}
