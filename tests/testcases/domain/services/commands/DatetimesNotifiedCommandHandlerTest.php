<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\DatetimesNotifiedCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

class DatetimesNotifiedCommandHandlerTest extends EE_UnitTestCase
{
    /**
     * @var DatetimesNotifiedCommandHandlerMock
     */
    private $command_handler_mock;

    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new DatetimesNotifiedCommandHandlerMock();
    }

    public function tearDown()
    {
        $this->command_handler_mock = null;
        parent::tearDown();
    }


    public function testSetDatetimesProcessedForAttendeeContext()
    {
        //create some events
        $datetimes = $this->factory->datetime->create_many(4);
        //set them processed
        $this->command_handler_mock->setDatetimesProcessed($datetimes, 'attendee');

        //validate whether they were set in the db.
        $this->assertEquals(
            4,
            EEM_Datetime::instance()->count(
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
            EEM_Datetime::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_ADMIN_TRACKER
                    )
                )
            )
        );
    }


    public function testSetDatetimesProcessedForAdminContext()
    {
        //create some events
        $datetimes = $this->factory->datetime->create_many(4);
        //set them processed
        $this->command_handler_mock->setDatetimesProcessed($datetimes, 'admin');

        //validate whether they were set processed in the db.
        $this->assertEquals(
            4,
            EEM_Datetime::instance()->count(
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
            EEM_Datetime::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::META_KEY_PREFIX_REGISTRATION_TRACKER
                    )
                )
            )
        );
    }
}
