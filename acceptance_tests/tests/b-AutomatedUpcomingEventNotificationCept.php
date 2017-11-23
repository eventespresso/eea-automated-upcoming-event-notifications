<?php

use Page\CoreAdmin;
use Page\AuenGeneral;
use Page\AuenMessageTemplate;
use Page\MessagesAdmin;
use Page\EventsAdmin;
use Page\TicketSelector;

//setup date for our test so the start date is at least one full day away from now.
$date = new DateTime('now + 36 hours', new DateTimeZone('UTC'));

/**
 * This test covers all testing of sending behaviour for the automated upcoming event
 * notification message type (see critical-functionality.md checklist)
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, AuenGeneral::ADDON_SLUG_FOR_WP_PLUGIN_PAGE, false);
$I->wantTo('This test covers all testing of sending behaviour for the automated upcoming event notification message type (see critical-functionality.md checklist)');

$I->loginAsAdmin();

//UNCOMMENT THE BELOW TO RUN THIS TEST IN ISOLATION.  IF YOU ARE TESTING WITH THIS BLOCK UNCOMMENTED THEN MAKE SURE
// EventEspressoAddonAcceptanceTester is instantiated with the "activate" flag set true.
// $I->activateWPCrontrolPlugin();
// $I->seeDailyScheduledEventSetup();
// $I->amOnMessageSettingsPage();
// $I->selectOption(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_ON_REQUEST_SELECTION_SELECTOR, '1');
// $I->click(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_SUBMIT_SELECTOR);
/** END ISOLATION TEST BLOCK. */

//let's create an event we'll use for testing
$I->amOnDefaultEventsListTablePage();
$I->click(EventsAdmin::ADD_NEW_EVENT_BUTTON_SELECTOR);
$I->see('Enter event title here');
$I->fillField(EventsAdmin::EVENT_EDITOR_TITLE_FIELD_SELECTOR, 'Event Test');

$I->see('Event Tickets & Datetimes');
$I->fillField(EventsAdmin::eventEditorDatetimeNameFieldSelector(), 'Datetime A');
$I->fillField(EventsAdmin::eventEditorDatetimeStartDateFieldSelector(), $date->format('Y-m-d h:i a'));
$I->fillField(
    EventsAdmin::eventEditorTicketNameFieldSelector(),
    'Ticket A'
);
$I->publishEvent();
$I->waitForText('Event published.', 20);
$link = $I->observeLinkUrlAt(EventsAdmin::EVENT_EDITOR_VIEW_LINK_AFTER_PUBLISH_SELECTOR);

//get event id
$event_id = $I->observeValueFromInputAt(EventsAdmin::EVENT_EDITOR_EVT_ID_SELECTOR);

//logout and do a couple registrations.
$I->logOut();
$I->amOnUrl($link);
$I->see('Event Test');
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '2');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitForText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Tester');
$I->fillOutLastNameFieldForAttendee('Guy');
$I->fillOutEmailFieldForAttendee('dude@example.org');
$I->goToNextRegistrationStep();
$I->waitForText('Congratulations', 15);


$I->loginAsAdmin();

//go and edit the default template and admin context so that it has the threshold changed to 3 days.
$I->amOnDefaultMessageTemplateListTablePage();
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_EVENT_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ADMIN_CONTEXT_SLUG
);
$I->see('Scheduling Settings');
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '1');
$I->fillField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');
$I->toggleContextState('Event Admin Recipient');
$I->click('Save');
$I->see('Scheduling Settings');
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');

//now let's go to crontrol and trigger the sends
$I->manuallyTriggerCronEvent();

//go and checkout what's on the message activity list table.
$I->amGoingTo('Verify that there is only one message for the admin context generated from this test.');
$I->amOnMessagesActivityListTablePage();
$I->dontSee(
    'dude@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->see(
    'admin@example.com',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT
    )
);

//verify count
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    0,
    'dude@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);


$I->amGoingTo('Do another registration.  Even with an additional registration meeting the criteria, there should be no more messages.');
$I->logOut();
$I->amOnUrl($link);
$I->see('Event Test');
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '2');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitForText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Tester B');
$I->fillOutLastNameFieldForAttendee('Guy');
$I->fillOutEmailFieldForAttendee('dudeb@example.org');
$I->goToNextRegistrationStep();
$I->waitForText('Congratulations', 15);

$I->loginAsAdmin();

//now let's go to crontrol and trigger the sends
$I->manuallyTriggerCronEvent();

//verify we did not get any more messages generated.
$I->amGoingTo('Verify there were no more messages generated.');
$I->amOnMessagesActivityListTablePage();
$I->dontSee(
    'dudeb@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->dontSee(
    'dude@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);

$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    0,
    'dude@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
//we expect one, because once a notification is sent for an event and the context its not sent again.
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);

//should be none for the new registration.
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    0,
    'dudeb@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);

$I->amGoingTo('Turn on attendee context for the message type and verify attendee emails are generated.');
$I->amOnDefaultMessageTemplateListTablePage();
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_EVENT_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ATTENDEE_CONTEXT_SLUG
);
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '1');
$I->fillField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');
$I->toggleContextState('Registrant Recipient');
$I->click('Save');
$I->see('Scheduling Settings');
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');

$I->amGoingTo('Trigger the cron event.');
$I->manuallyTriggerCronEvent(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);

$I->amGoingTo('Verify that there were no additional messages sent for admin context and we have messages for the previous registrations completed.');
$I->amOnMessagesActivityListTablePage();
$I->see(
    'dude@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->see(
    'dudeb@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant',
        '',
        2
    )
);

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
    1,
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);
