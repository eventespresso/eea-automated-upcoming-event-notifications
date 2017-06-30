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
        $this->command_handler_mock->setRegistrationsProcessed($registrations, 'testing');

        //validate whether they were set in the db.
        $this->assertEquals(
            4,
            EEM_Registration::instance()->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => Domain::REGISTRATION_TRACKER_PREFIX . 'testing'
                    )
                )
            )
        );
    }

}
