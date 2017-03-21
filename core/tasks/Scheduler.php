<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\tasks;

use EEH_DTT_Helper;
use EE_Registry;
use EventEspresso\AutomatedUpcomingEventNotifications\core\entities\SchedulingSettings;
use EEM_Message_Template_Group;
use EE_Message_Template_Group;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');


/**
 * Class handles setting the schedule and callbacks for wp-cron events handling the automation of sending upcoming
 * messages for events and datetimes
 *
 * @package    EventEspresso\AutomatedUpcomingEventnotifications
 * @subpackage core\tasks
 * @author     Darren Ethier
 * @since      1.0.0
 */
class Scheduler
{

    /**
     * Scheduler constructor.
     */
    public function __construct()
    {
        //register tasks (this is on the hook that will fire on EEH_Activation)
        add_action('FHEE__EEH_Activation__get_cron_tasks', array($this, 'registerScheduledTasks'));

        //register callbacks for scheduled events.
        add_action(
            'AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check',
            array($this, 'checkForUpcomingDatetimeNotificationsToSchedule')
        );
        add_action(
            'AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check',
            array($this, 'checkForUpcomingEventNotificationsToSchedule')
        );
    }


    /**
     * Callback for `FHEE__EEH_Activation__get_cron_tasks` that is used to register the schedule for this wp-cron.
     * @param  array $tasks  Already registered tasks.
     * @return array
     */
    public function registerScheduledTasks($tasks)
    {
        EE_Registry::instance()->load_helper('DTT_Helper');
        //this will set the schedule to the nearest upcoming midnight as a recurring daily schedule.
        $tasks['AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check'] = array(
            EEH_DTT_Helper::tomorrow(),
            'daily'
        );
        return $tasks;
    }


    /**
     * This is the callback on the AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check
     * schedule and queries the database for any upcoming Datetimes that meet the criteria for any message
     * template groups that are active for automation.
     */
    public function checkForUpcomingDatetimeNotificationsToSchedule()
    {
        //first get all message template groups for the EE_Automated_Upcoming_Datetime_message_type that are set to active.
        $message_template_groups = apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__checkForUpcomingDatetimeNotificationsToSchedule__message_template_groups',
            $this->getActiveMessageTemplateGroupsForAutomation('automate_upcoming_datetime')
        );
        if (empty($message_template_groups)) {
            return;
        }

        $trigger = new TriggerUpcomingDatetimeNotifications($message_template_groups);
        $trigger->run();
    }


    /**
     * This is the callback on the AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check
     * schedule and queries the database for any upcoming Events that meet the criteria for any message
     * template groups that are active for automation.
     */
    public function checkForUpcomingEventNotificationsToSchedule()
    {
        $message_template_groups = apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__checkForUpcomingEventNotificationsToSchedule__message_template_groups',
            $this->getActiveMessageTemplateGroupsForAutomation('automate_upcoming_event')
        );
        if (empty($message_template_groups)) {
            return;
        }
        $trigger = new TriggerUpcomingEventNotifications($message_template_groups);
        $trigger->run();
    }




    /**
     * Used to retrieve all active message template groups for the given message type.
     * @param $message_type
     * @return EE_Message_Template_Group[]
     */
    protected function getActiveMessageTemplateGroupsForAutomation($message_type)
    {
        $where = array(
            'MTP_message_type' => $message_type,
            'MTP_deleted' => 0,
            'Extra_Meta.EXM_key' => SchedulingSettings::AUTOMATION_ACTIVE,
            'Extra_Meta.EXM_value' => 1
        );
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Message_Template_Group::instance()->get_all(array($where));
    }
}
