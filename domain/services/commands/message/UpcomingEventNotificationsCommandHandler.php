<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EEM_Registration;
use EE_Registration;
use EE_Base_Class;
use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidIdentifierException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
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
        /**
         * @var int $message_template_group_id
         * @var EE_Registration[] $context_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_and_registrations) {
            /**
             * @var string $context
             * @var EE_Registration[] $registrations
             */
            foreach ($context_and_registrations as $context => $registrations) {
                $this->triggerMessages($registrations, Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT, $context);
                $this->setRegistrationsProcessed($registrations, $context, 'EVT');
            }
        }
    }


    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $registration_ids_to_exclude
     * @return array An array of data for processing.
     * @throws EE_Error
     */
    protected function getDataForCustomMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $registration_ids_to_exclude
    ) {
        $additional_query_parameters = $registration_ids_to_exclude
            ? array('REG_ID' => array('NOT IN', $registration_ids_to_exclude))
            : array();
        $registrations = $this->getRegistrationsForMessageTemplateGroup(
            $scheduling_settings,
            $context,
            $additional_query_parameters
        );
        return $registrations ? $registrations : array();
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[]|SchedulingSettings $scheduling_settings
     * @param string                                         $context
     * @param array                                          $data
     * @param array                                          $registration_ids_to_exclude
     * @return array
     * @throws EE_Error
     */
    protected function getDataForGlobalMessageTemplateGroup(
        SchedulingSettings $scheduling_settings,
        $context,
        array $data,
        array $registration_ids_to_exclude
    ) {
        if (! $scheduling_settings->getMessageTemplateGroup()->is_global()) {
            return array();
        }

        //extract the ids of registrations already in the data array.
        $registration_ids = isset($data[$context])
            ? array_keys($data[$context])
            : array();
        $registration_ids = array_unique(array_merge($registration_ids, $registration_ids_to_exclude));
        $additional_where_parameters = array();
        if ($registration_ids) {
            $additional_where_parameters['REG_ID'] = array('NOT IN', $registration_ids);
        }
        $registrations = $this->getRegistrationsForMessageTemplateGroup(
            $scheduling_settings,
            $context,
            $additional_where_parameters
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
                    $this->getStartTimeForQuery() + (DAY_IN_SECONDS * $settings->currentThreshold($context)),
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
        return $this->registration_model->get_all(array($where, 'group_by' => 'REG_ID'));
    }

    /**
     * Combines data for this handler.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param string                    $context The context for the data
     * @param array                     $data    The data to be aggregated
     * @param EE_Registration[]         $registrations
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
        foreach ($registrations as $registration) {
            $data[$message_template_group->ID()][$context][$registration->ID()] = $registration;
        }
        return $data;
    }


    /**
     * The purpose of this method is to get all the ids for approved registrations for published, upcoming events that
     * HAVE been notified at some point.  These registrations will then be excluded from the query for what
     * registrations to send notifications for.
     *
     * @param $context
     * @return array An array of registration ids.
     * @throws EE_Error
     */
    protected function registrationIdsAlreadyNotified($context)
    {
        $meta_key = $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
        $where = array(
            'Event.status'                 => array('IN', $this->eventStatusForRegistrationsQuery()),
            'Event.Datetime.DTT_EVT_start' => array('>', time()),
            'STS_ID'                       => EEM_Registration::status_id_approved,
            'REG_deleted'                  => 0,
            'Extra_Meta.EXM_key'           => $meta_key . 'EVT',
        );
        return $this->registration_model->get_col(array($where));
    }
}
