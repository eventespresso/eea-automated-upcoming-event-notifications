<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\UpcomingDatetimeNotificationsCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

class UpcomingDatetimeNotificationsCommandHandlerTests extends AddonTestCase
{

    /**
     * @var UpcomingDatetimeNotificationsCommandHandlerMock;
     */
    private $command_handler_mock;


    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new UpcomingDatetimeNotificationsCommandHandlerMock();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->command_handler_mock = null;
    }



    public function testGetDataForUpcomingDatetimeMessageType()
    {
        //first lets' make sure that on initial call, there is no data. There shouldn't be because none of the groups
        //have been made active.
        $this->assertEmpty($this->command_handler_mock->getData($this->message_template_groups['datetime']));

        //k now let's activate just the global message template group and set the date for our groups
        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['datetime']
        );
        $this->assertInstanceOf('EE_Message_Template_Group', $global_group);
        $global_group->activate_context('admin');
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_datetime'
        );
        //okay so our data should include the expected datetime plus one registration on just the global template group id
        $data = $this->command_handler_mock->getData($this->message_template_groups['datetime']);
        $this->assertCount(1, $data);
        //the key should = that of the global template
        $this->assertEquals($global_group->ID(), key($data));
        //the value of the global message template group index in the array should just have a count of one.
        $this->assertCount(
            1,
            $data[$global_group->ID()]
        );

        //go to the next level.
        $data = $data[$global_group->ID()];
        $this->assertEquals('admin', key($data));
        //pop datetime and registrations off of the context array.
        $data = array_pop($data['admin']);
        $this->assertInstanceOf('EE_Datetime', $data[0]);
        $this->assertEquals($expected_datetime->ID(), $data[0]->ID());
        $this->assertCount(1, $data[1]);
        $this->assertInstanceOf('EE_Registration', reset($data[1]));

        //k let's set the Global Message Template Group to inactive and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_group->deactivate_context('admin');
        $custom_mtg_settings = array();
        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['datetime'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $custom_mtg_settings[$message_template_group->ID()] = new SchedulingSettings($message_template_group);
            $message_template_group->activate_context('admin');
        }
        $this->assertEmpty($this->command_handler_mock->getData($this->message_template_groups['datetime']));

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a
        // custom message template group.
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_datetime',
            true
        );
        $global_group->activate_context('admin');

        //k this time we expect the global template to have one datetime and registration on it and only one custom
        // message template group with one datetime and registration on it.
        $data = $this->command_handler_mock->getData($this->message_template_groups['datetime']);
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $datetimes_and_registrations) {
            $this->assertEquals('admin', key($datetimes_and_registrations));
            $datetimes_and_registrations = array_pop($datetimes_and_registrations['admin']);
            $this->assertInstanceOf('EE_Datetime', $datetimes_and_registrations[0]);
            $this->assertCount(1, $datetimes_and_registrations[1]);
            $this->assertInstanceOf('EE_Registration', reset($datetimes_and_registrations[1]));
        }
    }


    public function testTriggerUpcomingDatetimeProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['datetime'])
        );
        //for each of our datetimes there should be no registrations processed and no admin processed.
        /** @var EE_Event $event */
        foreach ($this->events as $event) {
            foreach ($event->datetimes() as $datetime) {
                $this->assertEmpty($this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
                $this->assertFalse($datetime->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
            }
        }

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $datetimes = $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['datetime'])
        );
        foreach ($datetimes as $datetime) {
            $this->assertEmpty($this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
            $this->assertFalse($datetime->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
        }

        //now let's set the message template groups to active
        $global_group = $this->command_handler_mock->extractGlobalMessageTemplateGroup(
            $this->message_template_groups['datetime']
        );
        $global_group->activate_context('admin');

        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['datetime'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $message_template_group->activate_context('admin');
        }
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['datetime'])
        );

        //now process again and there should be NO registrations marked processed for each datetime but there SHOULD
        //be a marker for admin processed.
        foreach ($datetimes as $datetime) {
            $this->assertCount(0, $this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
            $this->assertTrue($datetime->get_extra_meta(Domain::META_KEY_PREFIX_ADMIN_TRACKER, true, false));
        }
    }
}
