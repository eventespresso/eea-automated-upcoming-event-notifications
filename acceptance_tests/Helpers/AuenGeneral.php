<?php

namespace EventEspresso\Codeception\helpers;

use Page\AuenMessageTemplate;


/**
 * AuenGeneral
 * General actor/module helpers for common tasks in tests for this add-on.
 *
 * @package EventEspresso\Codeception\helpers
 * @author  Darren Ethier
 * @since   1.0.0
 */
trait AuenGeneral
{

    /**
     * Verify that the daily scheduled event is setup correctly for the scheduled event for this addon.
     */
    public function seeScheduledCronSetup()
    {
        $this->actor()->seeCronHookSet(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);
        $this->actor()->seeCronEventScheduleIsSet(
            AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK,
            'ee_every_three_hours'
        );
    }


    /**
     * Manually trigger a cron event using the WP Crontrol plugin using the given cron event reference.
     */
    public function manuallyTriggerCronEvent()
    {
        $this->actor()->triggerCronEventToRun(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);
    }

}