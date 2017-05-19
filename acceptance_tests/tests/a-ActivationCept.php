<?php

use Page\CoreAdmin;
use Page\AuenGeneral;
use Page\AuenMessageTemplate;
use Page\MessagesAdmin;

/**
 * This test run covers the `Add-on activation` section for the critical-functionality.md checklist.
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, AuenGeneral::ADDON_SLUG_FOR_WP_PLUGIN_PAGE);
$I->wantTo('Run "Add-on Activation" section for the critical-functionality.md checklist');
$I->loginAsAdmin();

//check that default templates are available in the Global Message Template list table.
$I->amOnDefaultMessageTemplateListTablePage();
$I->waitForText('Automated Upcoming Event Notification');
$I->see('Automated Upcoming Datetime Notification');

//make sure these message types have the metabox for scheduling with the two settings.
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_DATETIME_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ADMIN_CONTEXT_SLUG
);
$I->see('Scheduling Settings');
$I->seeElement(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR);
$I->seeElement(AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR);
//make sure automation is turned off
$I->seeOptionIsSelected(
    AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR,
    'Off'
);
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_EVENT_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ADMIN_CONTEXT_SLUG
);
$I->see('Scheduling Settings');
$I->seeElement(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR);
$I->seeElement(AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR);
//make sure automation is turned off
$I->seeOptionIsSelected(
    AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR,
    'Off'
);
$I->moveBack();
$I->clickToEditMessageTemplateByMessageType(
    MessagesAdmin::PAYMENT_FAILED_MESSAGE_TYPE_SLUG,
    MessagesAdmin::PRIMARY_ATTENDEE_CONTEXT_SLUG
);
$I->dontSee('Scheduling Settings');


//make sure wp-crontrol is active. The plugin was included in the install via ee-codeception.yml
//check that the event for the messages schedule is present.
$I->activateWPCrontrolPlugin();
$I->seeDailyScheduledEventSetup();

//for the purpose of all tests we need send on same request to be set for messages
$I->amOnMessageSettingsPage();
$I->selectOption(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_ON_REQUEST_SELECTION_SELECTOR, '1');
$I->click(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_SUBMIT_SELECTOR);
