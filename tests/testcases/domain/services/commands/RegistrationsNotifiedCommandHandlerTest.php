<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\RegistrationsNotifiedCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

class RegistrationsNotifiedCommandHandlerTest extends EE_UnitTestCase
{
    /**
     * @var RegistrationsNotifiedCommandHandlerMock
     */
    private $command_handler_mock;

    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new RegistrationsNotifiedCommandHandlerMock();
    }

    public function tearDown()
    {
        $this->command_handler_mock = null;
        parent::tearDown();
    }


    public function testSetRegistrationsProcessed()
    {
        //create some registrations
        $registrations = $this->factory->registration->create_many(4);
        //set them processed
        $this->command_handler_mock->setRegistrationsProcessed($registrations, 'attendee', 'testing');

        //validate whether they were set in the db.
        $this->assertEquals(
            4,
            EEM_Registration::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_REGISTRATION_TRACKER . 'testing'
                    )
                )
            )
        );
    }


    public function testSetAdminProcessed()
    {
        /** @var EE_Event $event_one */
        $event_one = $this->factory->event->create();
        /** @var EE_Event $event_two */
        $event_two = $this->factory->event->create();
        //create some registrations with events.
        $registrations_for_event_group_one = $this->factory->registration->create_many(
            2,
            array('EVT_ID' => $event_one->ID())
        );
        $registrations_for_event_group_two = $this->factory->registration->create_many(
            3,
            array('EVT_ID' => $event_two->ID())
        );

        //merge so we should have two groups of registrations for two events.
        $registrations = array_merge($registrations_for_event_group_one, $registrations_for_event_group_two);
        //set them processed
        $this->command_handler_mock->setRegistrationsProcessed($registrations, 'admin', 'testing');
        //validate that registrations were NOT set processed in the db (because they only get set processed when
        //processing for attendee context.
        $this->assertEquals(
            0,
            EEM_Registration::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_REGISTRATION_TRACKER . 'testing'
                    )
                )
            )
        );

        //validate that the registrations were set processed for the admin context.
        $this->assertEquals(
            5,
            EEM_Registration::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_ADMIN_TRACKER . 'testing'
                    )
                )
            )
        );
    }
}
