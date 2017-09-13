<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\tasks;

use EE_Error;
use EEH_DTT_Helper;
use EEM_Message_Template_Group;
use EE_Message_Template_Group;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\loaders\Loader;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

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
     * @var CommandBusInterface
     */
    protected $command_bus;


    /**
     * @var EEM_Message_Template_Group
     */
    protected $message_template_group_model;


    /**
     * @var Loader
     */
    protected $loader;

    /**
     * Scheduler constructor.
     *
     * @param CommandBusInterface        $command_bus
     * @param EEM_Message_Template_Group $message_template_group_model
     * @param Loader                     $loader
     */
    public function __construct(
        CommandBusInterface $command_bus,
        EEM_Message_Template_Group $message_template_group_model,
        Loader $loader
    ) {
        $this->command_bus = $command_bus;
        $this->loader      = $loader;
        $this->message_template_group_model = $message_template_group_model;
        //register tasks (this is on the hook that will fire on EEH_Activation)
        add_filter('FHEE__EEH_Activation__get_cron_tasks', array($this, 'registerScheduledTasks'));

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
     *
     * @param  array $tasks Already registered tasks.
     * @return array
     */
    public function registerScheduledTasks($tasks)
    {
        //this will set the schedule to the nearest upcoming midnight as a recurring daily schedule.
        $tasks['AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check'] = array(
            EEH_DTT_Helper::tomorrow(),
            'daily',
        );
        return $tasks;
    }


    /**
     * This is the callback on the AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check
     * schedule and queries the database for any upcoming Datetimes that meet the criteria for any message
     * template groups that are active for automation.
     *
     * @throws EE_Error
     */
    public function checkForUpcomingDatetimeNotificationsToSchedule()
    {
        //first get all message template groups for the EE_Automated_Upcoming_Datetime_message_type that are set to active.
        $message_template_groups = apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__checkForUpcomingDatetimeNotificationsToSchedule__message_template_groups',
            $this->getActiveMessageTemplateGroupsForAutomation(Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME)
        );
        if (empty($message_template_groups)) {
            return;
        }

        //execute command
        $this->command_bus->execute(
            $this->loader->getNew(
                'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingDatetimeNotificationsCommand',
                array($message_template_groups)
            )
        );
    }


    /**
     * This is the callback on the AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check
     * schedule and queries the database for any upcoming Events that meet the criteria for any message
     * template groups that are active for automation.
     *
     * @throws EE_Error
     */
    public function checkForUpcomingEventNotificationsToSchedule()
    {
        $message_template_groups = apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__checkForUpcomingEventNotificationsToSchedule__message_template_groups',
            $this->getActiveMessageTemplateGroupsForAutomation(Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT)
        );
        if (empty($message_template_groups)) {
            return;
        }
        $this->command_bus->execute(
            $this->loader->getNew(
                'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message\UpcomingEventNotificationsCommand',
                array($message_template_groups)
            )
        );
    }


    /**
     * Used to retrieve all active message template groups for the given message type.
     *
     * @param $message_type
     * @return EE_Message_Template_Group[]
     * @throws EE_Error
     */
    protected function getActiveMessageTemplateGroupsForAutomation($message_type)
    {
        $where = array(
            'MTP_message_type'     => $message_type,
            'MTP_deleted'          => 0,
            'Extra_Meta.EXM_key'   => Domain::META_KEY_AUTOMATION_ACTIVE,
            'Extra_Meta.EXM_value' => 1,
        );
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->message_template_group_model->get_all(array($where));
    }
}
