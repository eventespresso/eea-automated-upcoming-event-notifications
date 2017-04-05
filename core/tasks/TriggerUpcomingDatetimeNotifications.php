<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\tasks;

use EventEspresso\AutomatedUpcomingEventNotifications\core\entities\SchedulingSettings;
use EE_Message_Template_Group;
use EEM_Datetime;
use EE_Datetime;
use EEM_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');


/**
 * The logic necessary for getting the data and processing the messages for Upcoming Datetime notifications
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \core\tasks
 * @author     Darren Ethier
 * @since      1.0.0
 */
class TriggerUpcomingDatetimeNotifications extends TriggerNotifications
{

    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     */
    protected function process($data)
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
     * This should handle setting up the data that would be sent into the process method.
     *
     * @return mixed
     */
    protected function getData()
    {
        $data = array();
        $custom_datetime_ids = array();
        //first we need to setup any Datetimes linked to the event associated with a custom template that meet the
        //threshold
        if (! empty($this->message_template_groups)) {
            array_walk(
                $this->message_template_groups,
                function (EE_Message_Template_Group $message_template_group) use (&$data, &$custom_datetime_ids) {
                    $settings = new SchedulingSettings($message_template_group);
                    //do a failsafe on whether this message template group is active first.
                    if (! $settings->isActive()) {
                        return;
                    }
                    $where = array(
                        'DTT_EVT_start' => array(
                            'BETWEEN',
                            array(
                                time(),
                                time() + (DAY_IN_SECONDS * $settings->currentThreshold())
                            )
                        ),
                        'Event.status' => 'publish',
                        'Event.Message_Template_Group.GRP_ID' => $message_template_group->ID()
                    );
                    $datetimes = EEM_Datetime::instance()->get_all(array($where));
                    //add datetimes to $data
                    if ($datetimes) {
                        array_walk(
                            $datetimes,
                            function (EE_Datetime $datetime) use (
                                $message_template_group,
                                &$data,
                                &$custom_datetime_ids
                            ) {
                                //get registration ids for each datetime and include with the array.
                                $where = array(
                                    'STS_ID' => EEM_Registration::status_id_approved,
                                    'Ticket.Datetime.DTT_ID' => $datetime->ID(),
                                    'REG_deleted' => 0,
                                    'OR' => array(
                                        'Extra_Meta.EXM_key' => array('IS NULL'),
                                        'Extra_Meta.EXM_key*exclude_tracker' => array('!=', self::REGISTRATION_TRACKER_PREFIX . 'DTT_' . $datetime->ID())
                                    )
                                );
                                $registrations = EEM_Registration::instance()->get_all(array($where));
                                $data[$message_template_group->ID()][$datetime->ID()] = array(
                                    $datetime,
                                    $registrations
                                );
                                $custom_datetime_ids[$datetime->ID()] = $datetime->ID();
                            }
                        );
                    }
                }
            );
        }

        //next is there a global template active?  If so, then we need to broaden our remaining trigger threshold but
        //exclude datetimes that are already queued up.
        if ($this->global_message_template_group) {
            $settings = new SchedulingSettings($this->global_message_template_group);
            //fail-safe... don't do anything if this group isn't active
            if (! $settings->isActive()) {
                return $data;
            }
            $where = array(
                'DTT_EVT_start' => array(
                    'BETWEEN',
                    array(
                        time(),
                        time() + (DAY_IN_SECONDS*$settings->currentThreshold())
                    )
                ),
                'Event.status' => 'publish',
            );
            if ($custom_datetime_ids) {
                $where['DTT_ID'] = array('NOT IN', $custom_datetime_ids);
            }
            $datetimes = EEM_Datetime::instance()->get_all(array($where));
            if ($datetimes) {
                array_walk(
                    $datetimes,
                    function (EE_Datetime $datetime) use (&$data) {
                        //get registration ids for each datetime and include with the array.
                        $where = array(
                            'STS_ID' => EEM_Registration::status_id_approved,
                            'Ticket.Datetime.DTT_ID' => $datetime->ID(),
                            'REG_deleted' => 0,
                            'OR' => array(
                                'Extra_Meta.EXM_key' => array('IS NULL'),
                                'Extra_Meta.EXM_key*exclude_tracker' => array('!=', self::REGISTRATION_TRACKER_PREFIX . 'DTT_' . $datetime->ID())
                            )
                        );
                        $registrations = EEM_Registration::instance()->get_all(array($where));
                        $data[$this->global_message_template_group->ID()][$datetime->ID()] = array(
                            $datetime,
                            $registrations
                        );
                    }
                );
            }
        }
        return $data;
    }
}
