<?php
/**
 * This test run covers the `Add-on activation` section for the critical-functionality.md checklist.
 */
$I = new EventEspressoAddonAcceptanceTester($scenario);
//check that default templates are available in the Global Message Template list table.
$I->amOnDefaultMessageTemplateListTablePage();
$I->see('Automated Upcoming Event Notification');
$I->see('Automated Upcoming Datetime Notification');

//make sure these message types have the metabox for scheduling with the two settings.
$I->clickToEditMessageTemplateByMessageType('automate_upcoming_datetime');
$I->see('Scheduling Settings');
$I->seeElement('#messages-scheduling-settings-automation-days-before');
$I->seeElement('#messages-scheduling-settings-automation-active');
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType('automate_upcoming_event');
$I->see('Scheduling Settings');
$I->seeElement('#messages-scheduling-settings-automation-days-before');
$I->seeElement('#messages-scheduling-settings-automation-active');
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType('payment');
$I->dontSee('Scheduling Settings');


//make sure wp-crontrol is active. The plugin was included in the install via ee-codeception.yml
//check that the event for the messages schedule is present.
$I->amOnPluginsPage();
$I->activatePlugin('wp-crontrol');
$I->click('Cron Events');
$I->see('AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check');
$I->see(
    '00:00:00',
    array(
        'xpath' => "//tr[starts-with(@id,'cron-AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check')]/td[4]"
    )
);
