<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EE_Base_Class;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EEM_Registration;
use EEM_Datetime;
use EE_Error;
use EE_Message_Template_Group;
use EE_Datetime;
use EE_Registration;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidIdentifierException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

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
     * @param CommandBusInterface     $command_bus
     * @param CommandFactoryInterface $command_factory
     * @param EEM_Registration        $registration_model
     * @param EEM_Datetime            $datetime_model
     */
    public function __construct(
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory,
        EEM_Registration $registration_model,
        EEM_Datetime $datetime_model
    ) {
        parent::__construct($command_bus, $command_factory, $registration_model);
        $this->datetime_model = $datetime_model;
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
        /**
         * @var int $message_template_group_id
         * @var array $context_datetimes_and_registrations
         */
        foreach ($data as $message_template_group_id => $context_datetimes_and_registrations) {
            /**
             * @var string $context
             * @var array  $datetimes_and_registrations
             */
            foreach ($context_datetimes_and_registrations as $context => $datetimes_and_registrations) {
                foreach ($datetimes_and_registrations as $datetime_and_registrations) {
                    $this->triggerMessages(
                        $datetime_and_registrations,
                        Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME,
                        $context
                    );
                    //extract the datetime so we can use for the processed reference.
                    $datetime = isset($datetime_and_registrations[0]) ? $datetime_and_registrations[0] : null;
                    if ($datetime instanceof EE_Datetime) {
                        //extract the registrations and mark them as having been notified.  Even though messages will
                        // get sent on a separate request, we don't have access to that so we simply mark them as
                        // having been processed.
                        $registrations = isset($datetime_and_registrations[1])
                            ? $datetime_and_registrations[1]
                            : array();
                        $this->setRegistrationsProcessed($registrations, $context, 'DTT_' . $datetime->ID());
                        if ($context === 'admin') {
                            $datetime->update_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true);
                        }
                    }
                }
            }
        }
    }


    /**
     * This retrieves the data containing registrations for all the custom message template groups.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @param array                       $registration_ids_to_exclude
     * @return array An array of data for processing.
     * @throws InvalidIdentifierException
     * @throws EE_Error
     */
    protected function getDataForCustomMessageTemplateGroups(
        array $message_template_groups,
        array $registration_ids_to_exclude
    ) {
        $data = array();
        foreach ($message_template_groups as $message_template_group) {
            $settings = new SchedulingSettings($message_template_group);
            //if there are no active contexts for this group then just continue for this group.
            $active_contexts = $settings->allActiveContexts();
            //if no contexts are active then just continue past this message template group.
            if (count($active_contexts) < 1) {
                continue;
            }

            //okay we have active contexts so lets setup the data for each context.
            foreach ($active_contexts as $context) {
                $date_data = $this->getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
                    $message_template_group,
                    $settings,
                    $context
                );
                if ($date_data) {
                    $data[$message_template_group->ID()][$context] = $date_data;
                }
            }
        }
        return $data;
    }


    /**
     * This retrieves the data containing registrations for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @param array                       $data
     * @param array                       $registration_ids_to_exclude
     * @return array
     * @throws InvalidIdentifierException
     * @throws EE_Error
     */
    protected function getDataForGlobalMessageTemplateGroup(
        array $message_template_groups,
        array $data,
        array $registration_ids_to_exclude
    ) {
        $global_message_template_group = $this->extractGlobalMessageTemplateGroup($message_template_groups);
        if (! $global_message_template_group instanceof EE_Message_Template_Group) {
            return $data;
        }

        $settings = new SchedulingSettings($global_message_template_group);

        //if there are no active contexts for the global group then get out.
        $active_contexts = $settings->allActiveContexts();
        if (count($active_contexts) < 1) {
            return $data;
        }

        //extract the ids of the datetimes already in the data so we exclude them from the global message template group
        //based query.
        $datetime_ids                         = $this->getDateTimeIdsFromData($data);
        $additional_datetime_where_conditions = array();
        if ($datetime_ids) {
            $additional_datetime_where_conditions['DTT_ID'] = array('NOT IN', $datetime_ids);
        }
        foreach ($active_contexts as $context) {
            $date_data = $this->getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
                $global_message_template_group,
                $settings,
                $context,
                $additional_datetime_where_conditions
            );
            if ($date_data) {
                $data[$global_message_template_group->ID()][$context] = $date_data;
            }
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
        /**
         * @var int   $group_id
         * @var array $datetime_records
         */
        foreach ($data as $group_id => $datetime_records) {
            /**
             * @var string $context
             * @var EE_Datetime[] $datetimes
             */
            foreach ($datetime_records as $context => $datetimes) {
                $datetime_ids = array_keys($datetime_records);
            }
        }
        return array_unique($datetime_ids);
    }


    /**
     * Build a query to get datetimes and registrations for the given message template group.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param SchedulingSettings        $settings
     * @param string                    $context
     * @param array                     $datetime_additional_where_conditions
     * @return array
     * @throws EE_Error
     */
    protected function getRegistrationsForDatetimeAndMessageTemplateGroupAndContext(
        EE_Message_Template_Group $message_template_group,
        SchedulingSettings $settings,
        $context,
        array $datetime_additional_where_conditions = array()
    ) {
        $data     = array();
        $datetimes = $this->getDatetimesForMessageTemplateGroupAndContext(
            $settings,
            $message_template_group,
            $context,
            $datetime_additional_where_conditions
        );
        if (! $datetimes) {
            return $data;
        }

        //get registration_ids_to_exclude for the given  but only if the context is not admin
        $registration_ids_to_exclude = $context !== 'admin'
            ? $this->registrationIdsToExclude($datetimes)
            : array();

        foreach ($datetimes as $datetime) {
            $registrations = $this->getRegistrationsForDatetime(
                $datetime,
                $registration_ids_to_exclude
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
     * @param string                    $context
     * @param array                     $additional_where_parameters
     * @return EE_Base_Class|EE_Datetime[]
     * @throws EE_Error
     */
    protected function getDatetimesForMessageTemplateGroupAndContext(
        SchedulingSettings $settings,
        EE_Message_Template_Group $message_template_group,
        $context,
        $additional_where_parameters = array()
    ) {
        $where = array(
            'DTT_EVT_start' => array(
                'BETWEEN',
                array(
                    $this->getStartTimeForQuery(),
                    $this->getStartTimeForQuery() + (DAY_IN_SECONDS * $settings->currentThreshold($context)),
                ),
            ),
            'Event.status'  => array('IN', $this->eventStatusForRegistrationsQuery()),
        );
        //if context is admin then let's add query params for excluding  admins that have been notified.
        if ($context === 'admin') {
            $where['OR'] = array(
                'Extra_Meta.EXM_key' => array('NOT IN', array(Domain::META_KEY_PREFIX_ADMIN_TRACKER)),
                'Extra_Meta.EXM_key*null' => array('IS NULL'),
            );
        }
        if ($additional_where_parameters) {
            $where = array_merge($where, $additional_where_parameters);
        }
        if ($message_template_group->is_global()) {
            $where['OR*global_conditions'] = array(
                'Event.Message_Template_Group.GRP_ID'      => $message_template_group->ID(),
                'Event.Message_Template_Group.GRP_ID*null' => array('IS NULL'),
            );
        } else {
            $where['Event.Message_Template_Group.GRP_ID'] = $message_template_group->ID();
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->datetime_model->get_all(array($where));
    }


    /**
     * Get registrations for given message template group and Datetime.
     *
     * @param EE_Datetime $datetime
     * @param array       $registration_ids_to_exclude
     * @return \EE_Base_Class[]|EE_Registration[]
     * @throws EE_Error
     */
    protected function getRegistrationsForDatetime(
        EE_Datetime $datetime,
        array $registration_ids_to_exclude = array()
    ) {
        //get registration ids for each datetime and include with the array.
        $where = array(
            'STS_ID'                 => EEM_Registration::status_id_approved,
            'Ticket.Datetime.DTT_ID' => $datetime->ID(),
            'REG_deleted'            => 0,
        );
        if ($registration_ids_to_exclude) {
            $where['REG_ID'] = array('NOT IN', $registration_ids_to_exclude);
        }
        return $this->registration_model->get_all(array($where));
    }


    /**
     * The purpose of this method is to get all the ids for approved registrations for published, upcoming events that
     * HAVE been notified at some point.  These registrations will then be excluded from the query for what
     * registrations to send notifications for.
     *
     * @return array  An array of registration ids.
     */
    protected function registrationIdsAlreadyNotified()
    {
        //we're not doing the query here because this message type allows for possibly sending messages for each
        // datetime so we need to delay the query until we have the datetimes to make the query for.
        return array();
    }


    /**
     * Gets the registrations for the given datetimes that have already been notified.
     *
     * @param EE_Datetime[] $datetimes
     * @return array
     * @throws EE_Error
     */
    protected function registrationIdsToExclude(array $datetimes)
    {
        //first prep our keys for the extra_meta
        $extra_meta_keys = array();
        foreach ($datetimes as $datetime) {
            $extra_meta_keys[] = Domain::META_KEY_PREFIX_REGISTRATION_TRACKER . 'DTT_' . $datetime->ID();
        }
        $where = array(
            'Ticket.Datetime.DTT_ID' => array('IN', array_keys($datetimes)),
            'STS_ID'                 => EEM_Registration::status_id_approved,
            'REG_deleted'            => 0,
            'Extra_Meta.EXM_key'     => array('IN', $extra_meta_keys),
        );
        return $this->registration_model->get_col(array($where));
    }
}
