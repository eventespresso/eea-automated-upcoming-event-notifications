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
     * Activate the WP Crontrol plugin.
     */
    public function activateWPCrontrolPlugin()
    {
        $this->actor()->amOnPluginsPage();
        $this->actor()->activatePlugin('wp-crontrol');
    }


    /**
     * Go to the cron events list table for the WP Crontrol plugin.
     */
    public function amOnWPCrontrolEventsPage()
    {
        $this->actor()->amOnAdminPage('tools.php?page=crontrol_admin_manage_page');
    }


    /**
     * Verify that the daily scheduled event is setup correctly for the scheduled event for this addon.
     */
    public function seeDailyScheduledEventSetUp()
    {
        $this->amOnWPCrontrolEventsPage();
        $this->actor()->see(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);
        $this->actor()->see(
            '00:00:00',
            array(
                'xpath' => "//tr[starts-with(@id,'cron-" . AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK . "')]/td[4]"
            )
        );
    }


    /**
     * Manually trigger a cron event using the WP Crontrol plugin using the given cron event reference.
     * @param string $cron_event_slug
     */
    public function manuallyTriggerCronEvent($cron_event_slug)
    {
        $this->amOnWPCrontrolEventsPage();
        $this->actor()->click(
            "Run Now",
            "//tr[starts-with(@id, 'cron-$cron_event_slug')]/td[6]/span[@class='row-actions visible']"
        );
        $this->actor()->see("Successfully executed the cron event $cron_event_slug");
    }

}