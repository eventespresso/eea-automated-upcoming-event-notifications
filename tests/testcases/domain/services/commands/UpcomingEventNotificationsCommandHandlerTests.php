<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\RegistrationsNotifiedCommandHandlerMock;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\UpcomingEventNotificationsCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

class UpcomingEventNotificationsCommandHandlerTests extends AddonTestCase
{

    /**
     * @var UpcomingEventNotificationsCommandHandlerMock;
     */
    private $command_handler_mock;


    /**
     * @var RegistrationsNotifiedCommandHandlerMock
     */
    private $registrations_notified_command_handler_mock;


    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new UpcomingEventNotificationsCommandHandlerMock();
        $this->registrations_notified_command_handler_mock = new RegistrationsNotifiedCommandHandlerMock();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->command_handler_mock = null;
        $this->registrations_notified_command_handler_mock = null;
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
        $this->setOneDateTimeOnEventToGivenDate(
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
        //if we pop the first array element that should have our expected data.
        $data = array_pop($data[$global_group->ID()]['admin']);
        $this->assertInstanceOf('EE_Registration', $data);

        //k let's set the Global Message Template Group to inactive and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_group->deactivate_context('admin');
        $custom_mtg_settings = array();
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
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event',
            true
        );
        $global_group->activate_context('admin');

        //k this time we expect the global template to have three registrations on it and only one custom message
        //template group with three registrations on it.
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $context_and_registrations) {
            foreach ($context_and_registrations as $context => $registrations) {
                $this->assertEquals('admin', $context);
                $this->assertCount(3, $registrations);
                $registrations = array_pop($registrations);
                $this->assertInstanceOf('EE_Registration', $registrations);
            }
        }
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
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event'
        );
        //okay so our data should include the expected datetime plus one registration on just the global template
        // group id
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);


        //so the count of registrations WITHOUT the admin notification flag set should be three.
        $this->assertCount(3, $data[$global_group->ID()]['admin']);
        $registrations = $data[$global_group->ID()]['admin'];

        //now let's set the notification flag as notified for these registrations.
        $this->registrations_notified_command_handler_mock->setRegistrationsProcessed(
            $registrations,
            'admin',
            'EVT'
        );

        //let's try getting data again
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        //this time there should be NO data.
        $this->assertEmpty($data);

        //let's loop through the registrations and just add some extra_meta on them.  This covers any tricky joins that
        //may result in incorrect results for queries.
        /** @var EE_Registration $registration */
        foreach ($registrations as $registration) {
            $registration->add_extra_meta('some_extra_meta_key', true);
        }
        //let's try getting data again
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        //there STILL should be no data.
        $this->assertEmpty($data);
    }

    
    public function testTriggerUpcomingEventNotificationProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        /** @var EE_Event $event */
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));
        //should be no admin processed records for the events
        foreach ($this->events as $event) {
            $this->assertFalse($event->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
        }

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));

        //same value expected for admin notifications
        foreach ($this->events as $event) {
            $this->assertFalse($event->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
        }

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
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));
        //messages triggered should have accumulated a total of 6 registrations
        $this->assertCount(6, $this->command_handler_mock->messages_triggered);
        foreach ($this->events as $event) {
            $this->assertFalse($event->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
        }


        //The expectation is that each registration will only ever get processed ONCE for an event (so if an event has
        //multiple datetimes, and that registration belonged to multiple datetimes, it would still only get one email
        // sent for that event, this message type, and the threshold given.  In this case triggering processing again
        // should result in no registrations being queued up for sending.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        //this should still be six.
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));

        //this should be empty (meaning no messages processed)
        $this->assertEmpty($this->command_handler_mock->messages_triggered);
    }
}
