<?php
namespace Page;

/**
 * AuenMessageTemplate
 * Selectors and references for the Automated Upcoming Event Notification acceptance tests.
 *
 * @package Page
 * @author  Darren Ethier
 * @since   1.0.0
 */
class AuenMessageTemplate
{

    /**
     * @var string
     */
    const UPCOMING_EVENT_MESSAGE_TYPE_SLUG = 'automate_upcoming_event';

    /**
     * @var string
     */
    const UPCOMING_DATETIME_MESSAGE_TYPE_SLUG = 'automate_upcoming_datetime';

    /**
     * The field where days before threshold is entered.  Found in the scheduling settings metabox when editing the
     * message template.
     * @var string
     */
    const AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR = '#messages-scheduling-settings-automation-days-before';


    /**
     * The field used to indicate whether the message template is active or not. Found in the scheduling settings metabox
     * when editing the message template.
     * @var string
     */
    const AUTOMATION_ACTIVE_SELECTION_SELECTOR = '#messages-scheduling-settings-automation-active';



    /**
     * The reference for the scheduled cron event.
     * @var string
     */
    const AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK = 'AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__check';
}