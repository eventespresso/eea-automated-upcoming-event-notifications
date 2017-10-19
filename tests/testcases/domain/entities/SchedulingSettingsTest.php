<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');


/**
 * SchedulingSettingsTest
 * Tests for the SchedulingSettings class
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage tests
 * @author  Darren Ethier
 * @since   1.0.0
 */
class SchedulingSettingsTest extends EE_UnitTestCase
{

    /**
     * @var SchedulingSettings;
     */
    private $scheduling_settings;


    /**
     * @var EE_Message_Template_Group;
     */
    private $message_template_group;

    public function setUp()
    {
        parent::setUp();
        $this->message_template_group = EEM_Message_Template_Group::instance()->get_one(
            array(
                array('MTP_message_type'=> Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT)
            )
        );
        $this->scheduling_settings = new SchedulingSettings($this->message_template_group);
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->scheduling_settings = null;
        $this->message_template_group = null;
    }



    public function testCurrentThreshold()
    {
        //first assert the default is set
        $this->assertEquals(1, $this->scheduling_settings->currentThreshold('admin'));

        //set it to something different
        $this->scheduling_settings->setCurrentThreshold(12, 'admin');
        $this->assertEquals(12, $this->scheduling_settings->currentThreshold('admin'));

        //and the same for attendee context
        $this->assertEquals(1, $this->scheduling_settings->currentThreshold('attendee'));

        //set to something different and test
        $this->scheduling_settings->setCurrentThreshold(12, 'attendee');
        $this->assertEquals(12, $this->scheduling_settings->currentThreshold('attendee'));
    }


    public function testAllActiveContexts()
    {
        $this->assertCount(0, $this->scheduling_settings->allActiveContexts());

        //set one of the contexts to active and verify
        $this->message_template_group->activate_context('admin');
        //context should be active now.
        $this->assertTrue($this->message_template_group->is_context_active('admin'));
        //expect active contexts to still be 0 for our existing scheduling settings object because of the cache
        $this->assertCount(0, $this->scheduling_settings->allActiveContexts());
        //so to verify the context got set to active reinstantiate a SchedulingSettings object
        $scheduling_settings = new SchedulingSettings($this->message_template_group);
        $active_contexts = $scheduling_settings->allActiveContexts();
        $this->assertCount(1, $active_contexts);

        //verify that 'admin' is the active context
        $this->assertTrue(in_array('admin', $active_contexts, true));
    }
}