<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\SchedulerMock;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * SchedulerTest
 * Tests the Scheduler class
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\
 * @subpackage tests
 * @author  Darren Ethier
 * @since   1.0.0
 */
class SchedulerTest extends EE_UnitTestCase
{

    /**
     * @var SchedulerMock;
     */
    private $scheduler_mock;


    public function setUp()
    {
        parent::setUp();
        $this->scheduler_mock = new SchedulerMock;
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->scheduler_mock = null;
    }

    
    public function testGetActiveMessageTemplateGroupsForAutomation()
    {
        $message_types_to_test = array(
            'automate_upcoming_datetime',
            'automate_upcoming_event'
        );

        foreach ($message_types_to_test as $message_type) {
            //there should already be a message template group for automate_upcoming_datetime and
            // automate_upcoming_event. let's verify that (we need to for setting up the test anyways).
            $message_template_group = EEM_Message_Template_Group::instance()->get_all(
                array(
                    array(
                        'MTP_message_type'=> $message_type
                    )
                )
            );
            //assert the count
            $this->assertCount(
                1,
                $message_template_group,
                sprintf('Testing %s', $message_type)
            );
            //extract the instance
            /** @var EE_Message_Template_Group $message_template_group */
            $message_template_group = reset($message_template_group);

            //assert that instance
            $this->assertInstanceOf(
                'EE_Message_Template_Group',
                $message_template_group,
                sprintf('Testing %s', $message_type)
            );

            //there should be NO results for this query because we haven't activated any automation yet
            $this->assertEmpty(
                $this->scheduler_mock->getActiveMessageTemplateGroupsForAutomation($message_type),
                sprintf('Testing %s', $message_type)
            );

            //now let's turn ON automation for this group.
            $message_template_group->activate_context('admin');

            //now our query should return one result
            $this->assertCount(
                1,
                $this->scheduler_mock->getActiveMessageTemplateGroupsForAutomation($message_type),
                sprintf('Testing %s', $message_type)
            );
        }
    }
}