<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\EventsNotifiedCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

class EventsNotifiedCommandHandlerTest extends EE_UnitTestCase
{
    /**
     * @var EventsNotifiedCommandHandlerMock
     */
    private $command_handler_mock;

    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new EventsNotifiedCommandHandlerMock();
    }

    public function tearDown()
    {
        $this->command_handler_mock = null;
        parent::tearDown();
    }


    public function testSetEventsProcessedForAttendeeContext()
    {
        //create some events
        $events = $this->factory->event->create_many(4);
        //set them processed
        $this->command_handler_mock->setEventsProcessed($events, 'attendee');

        //validate whether they were set in the db.
        $this->assertEquals(
            4,
            EEM_Event::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_REGISTRATION_TRACKER
                    )
                )
            )
        );

        //the events should not be marked processed for the admin context
        $this->assertEquals(
            0,
            EEM_Event::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_ADMIN_TRACKER
                    )
                )
            )
        );
    }


    public function testSetEventsProcessedForAdminContext()
    {
        //create some events
        $events = $this->factory->event->create_many(4);
        //set them processed
        $this->command_handler_mock->setEventsProcessed($events, 'admin');

        //validate whether they were set processed in the db.
        $this->assertEquals(
            4,
            EEM_Event::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_ADMIN_TRACKER
                    )
                )
            )
        );

        //ensure attendee context were NOT set processed.
        $this->assertEquals(
            0,
            EEM_Event::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_REGISTRATION_TRACKER
                    )
                )
            )
        );
    }
}
