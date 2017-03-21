<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\tasks;

use EE_Message_Template_Group;
use EEM_Registration;
use EE_Registration;
use EventEspresso\AutomatedUpcomingEventNotifications\core\entities\SchedulingSettings;
use EED_Automated_Upcoming_Event_Notifications;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');


/**
 * The logic necessary for getting the data and processing the messages for Upcoming Event notifications
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \core\tasks
 * @author     Darren Ethier
 * @since      1.0.0
 */
class TriggerUpcomingEventNotifications extends TriggerNotifications
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
        foreach ($data as $message_template_group_id => $registrations) {
            EED_Automated_Upcoming_Event_Notifications::prep_and_queue_messages(
                'automate_upcoming_event',
                $registrations
            );
            $this->setRegistrationsProcessed($registrations, 'EVT');
        }
    }

    /**
     * This should handle setting up the data that would be sent into the process method.
     *
     * @return array
     */
    protected function getData()
    {
        $data = array();
        $registrations_queued = array();
        if (! empty($this->message_template_groups)) {
            array_walk(
                $this->message_template_groups,
                function (EE_Message_Template_Group $message_template_group) use (&$data) {
                    $settings = new SchedulingSettings($message_template_group);
                    $where = array(
                        'Event.status' => 'publish',
                        'Event.Message_Template_Group.GRP_ID' => $message_template_group->ID(),
                        'Event.Datetime.DTT_EVT_start' => array(
                            'BETWEEN',
                            array(
                                time(),
                                time() + (DAY_IN_SECONDS * $settings->currentThreshold())
                            )
                        ),
                        'STS_ID' => EEM_Registration::status_id_approved,
                        'REG_deleted' => 0,
                        'OR' => array(
                            'Extra_Meta.EXM_key' => array('IS NULL'),
                            'Extra_Meta.EXM_key*exclude_tracker' => array('!=', self::REGISTRATION_TRACKER_PREFIX . 'EVT')
                        )
                    );
                    $registrations = EEM_Registration::instance()->get_all(array($where));
                    if ($registrations) {
                        array_walk(
                            $registrations,
                            function (EE_Registration $registration) use (
                                &$data,
                                &$registrations_queued,
                                $message_template_group
                            ) {
                                $data[$message_template_group->ID()][$registration->ID()][] = $registration;
                                $registrations_queued[$registration->ID()][] = $registration->ID();
                            }
                        );
                    }
                }
            );
        }

        //is there any global template active?  If so, then let's use that to get registrations for that threshold
        //excluding registrations that have already been queued up for custom templates.
        if ($this->global_message_template_group) {
            $settings = new SchedulingSettings($this->global_message_template_group);
            $where = array(
                'Event.status' => 'publish',
                'Event.Datetime.DTT_EVT_start' => array(
                    'BETWEEN',
                    array(
                        time(),
                        time() + (DAY_IN_SECONDS * $settings->currentThreshold())
                    )
                ),
                'STS_ID' => EEM_Registration::status_id_approved,
                'REG_deleted' => 0,
            );
            if ($registrations_queued) {
                $where['REG_ID'] = array('NOT IN', $registrations_queued);
            }
            $registrations = EEM_Registration::instance()->get_all(array($where));
            if ($registrations) {
                $data[$this->global_message_template_group->ID()] = array($registrations);
            }
        }
        return $data;
    }
}