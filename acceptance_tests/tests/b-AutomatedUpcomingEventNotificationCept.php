<?php

use Page\CoreAdmin;
use Page\AuenGeneral;
use Page\AuenMessageTemplate;
use Page\MessagesAdmin;
use Page\EventsAdmin;
use Page\TicketSelector;

//setup date for our test so the start date is within one day
$date = new DateTime('now + 1 day', new DateTimeZone('UTC'));

/**
 * This test covers all testing of sending behaviour for the automated upcoming event
 * notification message type (see critical-functionality.md checklist)
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, AuenGeneral::ADDON_SLUG_FOR_WP_PLUGIN_PAGE, false);
$I->wantTo('This test covers all testing of sending behaviour for the automated upcoming event notification message type (see critical-functionality.md checklist)');

$I->loginAsAdmin();

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
$I->click(EventsAdmin::EVENT_EDITOR_PUBLISH_BUTTON_SELECTOR);
$I->waitForText('Event published.');
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
$I->waitforText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Tester');
$I->fillOutLastNameFieldForAttendee('Guy');
$I->fillOutEmailFieldForAttendee('dude@example.org');
$I->goToNextRegistrationStep();
$I->waitForText('Congratulations');


$I->loginAsAdmin();

//go and edit the default template so that it has the threshold changed to 2 days.
$I->amOnDefaultMessageTemplateListTablePage();
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->clickToEditMessageTemplateByMessageType(
    AuenMessageTemplate::UPCOMING_EVENT_MESSAGE_TYPE_SLUG,
    MessagesAdmin::ADMIN_CONTEXT_SLUG
);
$I->see('Scheduling Settings');
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '5');
$I->fillField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');
$I->selectOption(AuenMessageTemplate::AUTOMATION_ACTIVE_SELECTION_SELECTOR, '1');
$I->click('Save');
$I->see('Scheduling Settings');
$I->seeInField(AuenMessageTemplate::AUTOMATION_DAYS_BEFORE_FIELD_SELECTOR, '3');

//now let's go to crontrol and trigger the sends
$I->manuallyTriggerCronEvent(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);

//go and checkout what's on the message activity list table.
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
    'admin@example.com',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT
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
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);


//now if we do another registration for this event and then trigger the cron again there should only be one additional email sent
//for the registrant and for the event admin
$I->logOut();
$I->amOnUrl($link);
$I->see('Event Test');
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '2');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitforText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Tester B');
$I->fillOutLastNameFieldForAttendee('Guy');
$I->fillOutEmailFieldForAttendee('dudeb@example.org');
$I->goToNextRegistrationStep();
$I->waitForText('Congratulations');

$I->loginAsAdmin();

//now let's go to crontrol and trigger the sends
//now let's go to crontrol and trigger the sends
$I->manuallyTriggerCronEvent(AuenMessageTemplate::AUTOMATION_DAILY_CHECK_CRON_EVENT_HOOK);

//verify we only have just the NEW messages generated (and no duplicates).
$I->amOnMessagesActivityListTablePage();
$I->see(
    'dudeb@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Automated Upcoming Event Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);

//verify count (should only be one instance of the original notification (but two to the event admin).
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
    2,
    'admin@example.com',
    'to',
    'Automated Upcoming Event Notification'
);

//only should be one of the new registration.
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dudeb@example.org',
    'to',
    'Automated Upcoming Event Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
