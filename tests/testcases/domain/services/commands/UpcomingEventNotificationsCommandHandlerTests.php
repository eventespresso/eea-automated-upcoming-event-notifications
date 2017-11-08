<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\ItemsNotifiedCommandHandlerMock;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\UpcomingEventNotificationsCommandHandlerMock;

class UpcomingEventNotificationsCommandHandlerTests extends AddonTestCase
{

    /**
     * @var UpcomingEventNotificationsCommandHandlerMock;
     */
    private $command_handler_mock;


    /**
     * @var ItemsNotifiedCommandHandlerMock
     */
    private $items_notified_command_handler_mock;


    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new UpcomingEventNotificationsCommandHandlerMock();
        $this->items_notified_command_handler_mock = new ItemsNotifiedCommandHandlerMock(EEM_Extra_Meta::instance());
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->command_handler_mock = null;
        $this->items_notified_command_handler_mock = null;
    }




    public function testGetDataForUpcomingEventMessageType()
    {
        //first lets' make sure that on initial call, there is no data. There shouldn't be because none of the groups
        //have been made active.
        $this->assertEmpty($this->command_handler_mock->getData($this->message_template_groups['event']));

        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['event']
        );

        //k now let's activate just the global message template group and set the date for our groups
        $global_group->activate_context('admin');
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event'
        );
        //okay so our data should include the expected datetime plus three registrations on just the global template
        // group id
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        $this->assertCount(1, $data);
        //the key should = that of the global template
        $this->assertEquals($global_group->ID(), key($data));
        //context should be set and as admin.
        $this->assertEquals('admin', key($data[$global_group->ID()]));
        //the value of the global message template group index in the array should have a count of 3 because the match
        // is against ANY datetime for the event within the threshold.  So that means all three registrations on the
        // event should get returned..
        $this->assertCount(3, $data[$global_group->ID()]['admin']);
        $registration_id = key($data[$global_group->ID()]['admin']);
        $registration_record = reset($data[$global_group->ID()]['admin']);
        $this->assertCount(3, $registration_record);
        $this->assertArrayHasKey('REG_ID', $registration_record);
        $this->assertArrayHasKey('ATT_ID', $registration_record);
        $this->assertArrayHasKey('EVT_ID', $registration_record);
        $this->assertEquals($registration_id, $registration_record['REG_ID']);


        //k let's set the Global Message Template Group to inactive and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_group->deactivate_context('admin');
        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['event'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $message_template_group->activate_context('admin');
        }
        $this->assertEmpty(
            $this->command_handler_mock->getData(
                $this->message_template_groups['event']
            )
        );

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a
        // custom message template group.
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event',
            true
        );
        $global_group->activate_context('admin');

        //k this time we expect the global template to have three registrations on it and only one custom message
        //template group with three registrations on it.
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $context_and_registration_records) {
            foreach ($context_and_registration_records as $context => $registration_records) {
                $this->assertEquals('admin', $context);
                $this->assertCount(3, $registration_records);
                $registration_id = key($registration_records);
                $registration_record_items = reset($registration_records);
                $this->assertCount(3, $registration_record_items);
                $this->assertArrayHasKey('REG_ID', $registration_record_items);
                $this->assertArrayHasKey('ATT_ID', $registration_record_items);
                $this->assertArrayHasKey('EVT_ID', $registration_record_items);
                $this->assertEquals($registration_id, $registration_record_items['REG_ID']);
            }
        }
    }


    public function testTriggerUpcomingEventNotificationProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        /** @var EE_Event $event */
        $this->assertEmpty($this->getEventsProcessed());
        //should be no admin processed records for the events
        $this->assertEmpty($this->getEventsProcessed('admin'));

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimeZone(get_option('timezone_string')));
        $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        $this->assertEmpty($this->getEventsProcessed());
        $this->assertEmpty($this->getEventsProcessed('admin'));

        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['event']
        );
        //now let's set the message template groups to active
        $global_group->activate_context('attendee');

        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['event'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $message_template_group->activate_context('attendee');
        }

        /**
         * The expectation here is because this is an _event_ based notification (for the attendee context), if the
         * event has ANY datetime matching the "upcoming" threshold, then all the registrations for that event will get
         * notified (regardless of whether they have access to the date triggering the message or not).  So this means
         * since our expectation data has 3 registrations per event, and our threshold should trigger two events to
         * match, we should get 6 registrations returned for our test.
         * However we should not see ANY admin notification recorded (because that was not activated).
         */
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        $this->assertCount(2, $this->getEventsProcessed());
        //messages triggered should have accumulated a total of 6 registrations
        $this->assertCount(6, $this->command_handler_mock->messages_triggered);
        $this->assertEmpty($this->getEventsProcessed('admin'));

        //The expectation is that each registration will only ever get processed ONCE for an event (so if an event has
        //multiple datetimes, and that registration belonged to multiple datetimes, it would still only get one email
        // sent for that event, this message type, and the threshold given.  In this case triggering processing again
        // should result in no registrations being queued up for sending.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        //this should still be six.
        $this->assertCount(2, $this->getEventsProcessed());

        //this should be empty (meaning no messages processed)
        $this->assertEmpty($this->command_handler_mock->messages_triggered);
    }


    /**
     * This simply makes sure that if an admin is marked as having already received notification for otherwise
     * conditions that sends notifications, that there will be no registrations returned for the admin to be notified
     * about.
     *
     * This is a more focused test than testGetDataForUpcomingEventMessageType so the number of assertions is lower
     * here.
     */
    public function testGetDataForUpcomingEventMessageTypeForAlreadyNotifiedAdmin()
    {
        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['event']
        );
        //activate just admin context for the test
        $global_group->activate_context('admin');
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event'
        );
        $expected_event = $expected_datetime->event();
        //okay so our data should include the expected datetime plus one registration on just the global template
        // group id
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);


        //so the count of registrations WITHOUT the admin notification flag set should be three.
        $this->assertCount(3, $data[$global_group->ID()]['admin']);
        $registrations = $data[$global_group->ID()]['admin'];

        $aggregated_events = $this->command_handler_mock->aggregateEventsForContext(
            $registrations,
            array(),
            'admin'
        );

        //verify we have $aggregated_events for the given context
        $this->assertArrayHasKey('admin', $aggregated_events);
        //verify the event id in this array matches the event the datetime was added to.
        $this->assertEquals($expected_event->ID(), reset($aggregated_events['admin']));

        //now let's set the notification flag as notified for these events.
        $this->items_notified_command_handler_mock->setItemsProcessed(
            $aggregated_events['admin'],
            EEM_Event::instance(),
            'admin'
        );

        // verify the single event was processed.
        $this->assertCount(1, $this->getEventsProcessed('admin'));

        //let's try getting data again
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);

        //this time there should be NO data.
        $this->assertEmpty($data);

        //let's loop through the registrations and just add some extra_meta on them.  This covers any tricky joins that
        //may result in incorrect results for queries.
        /** @var EE_Registration $registration */
        foreach (array_keys($registrations) as $registration_id) {
            $registration = EEM_Registration::instance()->get_one_by_ID($registration_id);
            $registration->add_extra_meta('some_extra_meta_key', true);
        }
        //let's try getting data again
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        //there STILL should be no data.
        $this->assertEmpty($data);
    }


    /**
     * In this test we're verifying:
     * - if multiple events (in our test we use 3) are authored by the same event author
     * - if one event is attached to a custom message template group.
     * - if the other two are attached to the global message template group.
     * - if all message template groups have the same set threshold.
     * - then the admin will receive a notification for just the registrations belonging to the event attached to
     *   the custom message template group and
     * - then the admin will receive one additional notification for the global template and registrations belonging to
     *   the remaining events.
     */
    public function testEventAddedToCustomMessageTemplateGroupForAdminContext()
    {
        //activate admin context for all event groups
        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['event'] as $message_template_group) {
            $message_template_group->activate_context('admin');
        }

        //set all events to within the threshold (defaults to 5 days via setup).
        $four_days_from_now = new DateTime('now +4 days', new DateTimeZone(get_option('timezone_string')));
        $this->setOneDatetimeOnEventsToDate($four_days_from_now, 3);

        //let's make sure we set up the rest of the data needed for generating actual messages.
        $this->setTransactionForEvents();

        $this->command_handler_mock->setTriggerActualMessages(true);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );

        //okay so now we can retrieve the messages for our expectations.  Let's just retrieve the admin messages.
        $messages_to_generate = EEM_Message::instance()->get_all();

        //let's go ahead and generate those puppies
        EED_Messages::generate_now(array_keys($messages_to_generate));


        //k now let's pull our messages for verification
        $expected_messages = EEM_Message::instance()->get_all(
            array(
                array(
                    'MSG_messenger' => 'email',
                    'MSG_message_type' => Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT,
                    'MSG_context' => 'admin'
                )
            )
        );

        //first we expect there to be two messages for the admin context.
        $this->assertCount(2, $expected_messages);

        //let's remap the keys of the array to the group_id for further testing.
        $mapped_messages = array();
        array_walk(
            $expected_messages,
            function (EE_Message $message, $message_id) use (&$mapped_messages) {
                $mapped_messages[$message->GRP_ID()] = $message;
            }
        );

        //next we expect that one of these is the global group and one of them is the custom group.
        $this->assertArrayHasKey($this->global_event_message_template_group_id, $mapped_messages);
        $this->assertArrayHasKey($this->custom_event_message_template_group_id, $mapped_messages);

        //next we expect that the global group message has two events listed
        /** @var EE_Message $global_group_message */
        $global_group_message = $mapped_messages[$this->global_event_message_template_group_id];
        $this->assertEquals(2, substr_count($global_group_message->content(), 'Event:'));

        //next we expect that the custom group message has only one event listed. And its the first event.
        /** @var EE_Message $custom_group_message */
        $custom_group_message = $mapped_messages[$this->custom_event_message_template_group_id];
        $this->assertEquals(1, substr_count($custom_group_message->content(), 'Event:'));
        $this->assertEquals(1, substr_count($custom_group_message->content(), 'Event attached to Custom Group'));
    }
}
