<?php

use Page\CoreAdmin;
/**
 * This test run covers the `Add-on activation` section for the critical-functionality.md checklist.
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, 'event-espresso-automated-upcoming-event-notification');
$I->wantTo('Run "Add-on Activation" section for the critical-functionality.md checklist');
$I->loginAsAdmin();

//check that default templates are available in the Global Message Template list table.
$I->amOnDefaultMessageTemplateListTablePage();
$I->waitForText('Automated Upcoming Event Notification');
$I->see('Automated Upcoming Datetime Notification');

//make sure these message types have the metabox for scheduling with the two settings.
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType('automate_upcoming_datetime', 'admin');
$I->see('Scheduling Settings');
$I->seeElement('#messages-scheduling-settings-automation-days-before');
$I->seeElement('#messages-scheduling-settings-automation-active');
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType('automate_upcoming_event', 'admin');
$I->see('Scheduling Settings');
$I->seeElement('#messages-scheduling-settings-automation-days-before');
$I->seeElement('#messages-scheduling-settings-automation-active');
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType('payment_failed', 'primary_attendee');
$I->dontSee('Scheduling Settings');


//make sure wp-crontrol is active. The plugin was included in the install via ee-codeception.yml
//check that the event for the messages schedule is present.
$I->amOnPluginsPage();
$I->activatePlugin('wp-crontrol');
$I->click("//span[@class='crontrol-events']/a");
$I->see('AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check');
$I->see(
    '00:00:00',
    array(
        'xpath' => "//tr[starts-with(@id,'cron-AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check')]/td[4]"
    )
);
