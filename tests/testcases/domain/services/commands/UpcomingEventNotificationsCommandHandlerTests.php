<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\UpcomingEventNotificationsCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

class UpcomingEventNotificationsCommandHandlerTests extends AddonTestCase
{

    /**
     * @var UpcomingEventNotificationsCommandHandlerMock;
     */
    private $command_handler_mock;


    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new UpcomingEventNotificationsCommandHandlerMock();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->command_handler_mock = null;
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
        $global_mtg_settings = new SchedulingSettings($global_group);
        $global_mtg_settings->setIsActive(true);
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event'
        );
        //okay so our data should include the expected datetime plus one registration on just the global template group id
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        $this->assertCount(1, $data);
        //the key should = that of the global template
        $this->assertEquals($global_group->ID(), key($data));
        //the value of the global message template group index in the array should have a count of 3 because the match is
        // against ANY datetime for the event within the threshold.  So that means all three registrations on the event
        // should get returned..
        $this->assertCount(3, $data[$global_group->ID()]);
        //if we pop the first array element that should have our expected data.
        $data = array_pop($data[$global_group->ID()]);
        $this->assertInstanceOf('EE_Registration', $data);

        //k let's set the Global Message Template Group to active and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_mtg_settings->setIsActive(false);
        $custom_mtg_settings = array();
        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['event'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $custom_mtg_settings[$message_template_group->ID()] = new SchedulingSettings($message_template_group);
            $custom_mtg_settings[$message_template_group->ID()]->setIsActive(true);
        }
        $this->assertEmpty(
            $this->command_handler_mock->getData(
                $this->message_template_groups['event']
            )
        );

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a custom
        //message template group.
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event',
            true
        );
        $global_mtg_settings->setIsActive(true);

        //k this time we expect the global template to have three registrations on it and only one custom message
        //template group with three registrations on it.
        $data = $this->command_handler_mock->getData($this->message_template_groups['event']);
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $registrations) {
            $this->assertCount(3, $registrations);
            $registrations = array_pop($registrations);
            $this->assertInstanceOf('EE_Registration', $registrations);
        }
    }


    public function testTriggerUpcomingEventNotificationProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        /** @var EE_Event $event */
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));

        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['event']
        );
        //now let's set the message template groups to active
        $global_settings = new SchedulingSettings($global_group);
        $global_settings->setIsActive(true);

        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['event'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $settings = new SchedulingSettings($message_template_group);
            $settings->setIsActive(true);
        }

        /**
         * The expectation here is because this is an _event_ based notification, if the event has ANY datetime
         * matching the "upcoming" threshold, then all the registrations for that event will get notified (regardless of
         * whether they have access to the date triggering the message or not).  So this means since our expectation data
         * has 3 registrations per event, and our threshold should trigger two events to match, we should get 6
         * registrations returned for our test.
         */
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));
        //messages triggered should have accumulated a total of 6 registrations
        $this->assertCount(6, $this->command_handler_mock->messages_triggered);


        //The expectation is that each registration will only ever get processed ONCE for an event (so if an event has
        //multiple datetimes, and that registration belonged to multiple datetimes, it would still only get one email sent
        //for that event, this message type, and the threshold given.  In this case triggering processing again should result
        //in no registrations being queued up for sending.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['event'])
        );
        //this should still be six.
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));

        //this should be empty (meaning no messages processed)
        $this->assertEmpty($this->command_handler_mock->messages_triggered);
    }
}
