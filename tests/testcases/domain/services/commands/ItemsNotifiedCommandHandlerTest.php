<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\ItemsNotifiedCommandHandlerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

class ItemsNotifiedCommandHandlerTest extends EE_UnitTestCase
{
    /**
     * @var ItemsNotifiedCommandHandlerMock
     */
    private $command_handler_mock;

    public function setUp()
    {
        parent::setUp();
        $this->command_handler_mock = new ItemsNotifiedCommandHandlerMock(EEM_Extra_Meta::instance());
    }

    public function tearDown()
    {
        $this->command_handler_mock = null;
        parent::tearDown();
    }


    public function testSetDatetimesProcessedForAttendeeContext()
    {
        //create datetimes and set them processed set them processed
        $this->command_handler_mock->setItemsProcessed(
            $this->extractIdsFromModelEntities(
                $this->factory->datetime->create_many(4)
            ),
            EEM_Datetime::instance(),
            'attendee'
        );

        $this->assertCountForItemsNotified(
            EEM_Datetime::instance(),
            Domain::META_KEY_PREFIX_REGISTRATION_TRACKER,
            4
        );

        $this->assertCountForItemsNotified(
            EEM_Datetime::instance(),
            Domain::META_KEY_PREFIX_ADMIN_TRACKER,
            0
        );
    }


    public function testSetDatetimesProcessedForAdminContext()
    {
        //create some datetimes and set them processed
        $this->command_handler_mock->setItemsProcessed(
            $this->extractIdsFromModelEntities(
                $this->factory->datetime->create_many(4)
            ),
            EEM_Datetime::instance(),
            'admin'
        );

        $this->assertCountForItemsNotified(
            EEM_Datetime::instance(),
            Domain::META_KEY_PREFIX_REGISTRATION_TRACKER,
            0
        );

        $this->assertCountForItemsNotified(
            EEM_Datetime::instance(),
            Domain::META_KEY_PREFIX_ADMIN_TRACKER,
            4
        );
    }



    public function testSetEventsProcessedForAttendeeContext()
    {
        //create datetimes and set them processed set them processed
        $this->command_handler_mock->setItemsProcessed(
            $this->extractIdsFromModelEntities(
                $this->factory->event->create_many(4)
            ),
            EEM_Event::instance(),
            'attendee'
        );

        $this->assertCountForItemsNotified(
            EEM_Event::instance(),
            Domain::META_KEY_PREFIX_REGISTRATION_TRACKER,
            4
        );

        $this->assertCountForItemsNotified(
            EEM_Event::instance(),
            Domain::META_KEY_PREFIX_ADMIN_TRACKER,
            0
        );
    }



    public function testSetEventsProcessedForAdminContext()
    {
        //create some datetimes and set them processed
        $this->command_handler_mock->setItemsProcessed(
            $this->extractIdsFromModelEntities(
                $this->factory->event->create_many(4)
            ),
            EEM_Event::instance(),
            'admin'
        );

        $this->assertCountForItemsNotified(
            EEM_Event::instance(),
            Domain::META_KEY_PREFIX_REGISTRATION_TRACKER,
            0
        );

        $this->assertCountForItemsNotified(
            EEM_Event::instance(),
            Domain::META_KEY_PREFIX_ADMIN_TRACKER,
            4
        );
    }


    /**
     * Helper function for simply asserting the given key has the expected count in the db for the given model.
     *
     * @param EEM_Base $model
     * @param string   $meta_key_asserted
     * @param int      $expected_count
     * @throws EE_Error
     */
    protected function assertCountForItemsNotified(EEM_Base $model, $meta_key_asserted, $expected_count)
    {
        $this->assertEquals(
            $expected_count,
            $model->count(
                array(
                    array(
                        'Extra_Meta.EXM_key' => $meta_key_asserted
                    )
                )
            ),
            sprintf(
                'Expected there to be %d %s entity records with the Extra_Meta.EXM_key of %s.',
                $expected_count,
                $model->get_this_model_name(),
                $meta_key_asserted
            )
        );
    }


    /**
     * Receives an array of model entities, extracts the values for the primary keys of those entities and returns them
     * as values in an array.
     *
     * @param EE_Base_Class[] $model_entities
     * @return array
     * @throws EE_Error
     */
    private function extractIdsFromModelEntities(array $model_entities)
    {
        $ids = array();
        foreach ($model_entities as $entity) {
            $ids[] = $entity->ID();
        }
        return $ids;
    }
}
