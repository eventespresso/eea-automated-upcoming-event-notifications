<?php
use Page\CoreAdmin;
use Page\AuenGeneral;
use Page\AuenMessageTemplate;
use Page\MessagesAdmin;
use Page\EventsAdmin;

//in this test we're going to use what was already setup as a part of `b-AutomatedUpcomingEventNotificationCept` test
//so the same event, registrations etc as what was done there will be used.

/**
 * This test covers all testing of sending behaviour for the automated upcoming datetime notification message type
 * (see critical-functionality.md checklist)
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, AuenGeneral::ADDON_SLUG_FOR_WP_PLUGIN_PAGE, false);
$I->wantTo('This test covers all testing of sending behaviour for the automated upcoming datetime notification message type (see critical-functionality.md checklist)');


//just testing the ability to browse to the single event page for a given event title from the context of the event list table
//will get removed after its verified the new helpers/page things work.
$I->loginAsAdmin();

//Our previous test will already have 4 registrations setup (that should only trigger 2 notifications for the registrants
//and 2 notifications for the event admin in this test).  This means that after we turn on datetime notifications
//and trigger the cron.  There should be:
// - 1 notifications for Automated Upcoming Event message type to the dude@example.org user
// - 1 notification for the above user to Automated Upcoming Datetime message type.
// - 1 notification for dudeb@example.org user to Automated Upcoming Event message type.
// - 1 notification for the above user to the Automated Upcoming Datetime message type.
// - 2 notifications to the Event Admin for Automated Upcoming Event message type.
// - 1 notification to the Event Admin for Automated Upcoming Datetime message type (this is because there is only one
//      triggered per group.

$I->amOnDefaultMessageTemplateListTablePage();
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_DATETIME_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ADMIN_CONTEXT_SLUG
);
$I->see('Scheduling Settings');
$I->selectOption(AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR, '1');
$I->click('Save');

$I->manuallyTriggerCronEvent(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);

$I->amOnMessagesActivityListTablePage();
//set per page to a higher value
$I->setPerPageOptionForScreen(20);
$I->see(
    'dude@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Datetime Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant',
        '',
        0
    )
);
$I->see(
    'dudeb@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Datetime Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant',
        '',
        0
    )
);
$I->see(
    'admin@example.com',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Datetime Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Event Admin',
        '',
        0
    )
);

//verify counts (and include event notification counts in here).
//verify count
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dude@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dudeb@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    2,
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);

$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dude@example.org',
    'to',
    'Automated Upcoming Datetime Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dudeb@example.org',
    'to',
    'Automated Upcoming Datetime Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'admin@example.com',
    'to',
    'Automated Upcoming Datetime Notification'
);