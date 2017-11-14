<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\UpcomingDatetimeNotificationsCommandHandlerMock;

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
        //okay so our data should include the expected datetime plus one registration on just the global template
        // group id
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
        //pop datetime_id and registration items off of the context array.
        $data = array_pop($data['admin']);
        $this->assertEquals($expected_datetime->ID(), $data[0]);
        $this->assertCount(1, $data[1]);
        $registration_id = key($data[1]);
        $registration_record = reset($data[1]);
        $this->assertCount(4, $registration_record);
        $this->assertArrayHasKey('REG_ID', $registration_record);
        $this->assertArrayHasKey('EVT_ID', $registration_record);
        $this->assertArrayHasKey('ATT_ID', $registration_record);
        $this->assertArrayHasKey('TXN_ID', $registration_record);
        $this->assertEquals($registration_id, $registration_record['REG_ID']);

        //k let's set the Global Message Template Group to inactive and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_group->deactivate_context('admin');
        /** @var EE_Message_Template_Group $message_template_group */
        foreach ($this->message_template_groups['datetime'] as $message_template_group) {
            if ($message_template_group->is_global()) {
                continue;
            }
            $message_template_group->activate_context('admin');
        }
        $this->assertEmpty($this->command_handler_mock->getData($this->message_template_groups['datetime']));

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a
        // custom message template group.
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_datetime',
            true
        );
        $global_group->activate_context('admin');

        //k this time we expect the global template to have one datetime and registration on it and only one custom
        // message template group with one datetime and registration on it.
        $data = $this->command_handler_mock->getData($this->message_template_groups['datetime']);
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $datetimeids_and_registration_items) {
            $this->assertEquals('admin', key($datetimeids_and_registration_items));
            $datetime_id = key($datetimeids_and_registration_items['admin']);
            $datetimeids_and_registration_items = reset($datetimeids_and_registration_items['admin']);
            $this->assertCount(1, $datetimeids_and_registration_items[1]);
            //each datetimeids_and_registration_items array element attached to the has the key as the datetime_id.
            //The first element in the array (value for that key) should be a matching datetime_id.
            $this->assertEquals($datetime_id, $datetimeids_and_registration_items[0]);
            $registration_id = key($datetimeids_and_registration_items[1]);
            $registration_record = reset($datetimeids_and_registration_items[1]);
            $this->assertCount(4, $registration_record);
            $this->assertArrayHasKey('REG_ID', $registration_record);
            $this->assertArrayHasKey('ATT_ID', $registration_record);
            $this->assertArrayHasKey('EVT_ID', $registration_record);
            $this->assertArrayHasKey('TXN_ID', $registration_record);
            $this->assertEquals($registration_id, $registration_record['REG_ID']);
        }
    }


    public function testTriggerUpcomingDatetimeProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['datetime'])
        );
        //for each of our datetimes there should be no registrations processed and no admin processed.
        $this->assertEmpty($this->getDatetimesProcessed());
        $this->assertEmpty($this->getDatetimesProcessed('admin'));

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $datetimes = $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->command_handler_mock->process(
            $this->command_handler_mock->getData($this->message_template_groups['datetime'])
        );
        $this->assertEmpty($this->getDatetimesProcessed());
        $this->assertEmpty($this->getDatetimesProcessed('admin'));

        //now let's set the message template groups to active for admin context
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

        $this->assertEmpty($this->getDatetimesProcessed());
        $this->assertCount(2, $this->getDatetimesProcessed('admin'));
    }
}
