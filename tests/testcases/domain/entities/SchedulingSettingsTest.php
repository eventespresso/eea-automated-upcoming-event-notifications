<?php

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

    public function setUp()
    {
        parent::setUp();
        $message_template_group = EE_Message_Template_Group::new_instance(
            array(
                'MTP_message_type' => 'automate_upcoming_event'
            )
        );
        $this->scheduling_settings = new SchedulingSettings($message_template_group);
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->scheduling_settings = null;
    }



    public function testCurrentThreshold()
    {
        //first assert the default is set
        $this->assertEquals(1, $this->scheduling_settings->currentThreshold());

        //set it to something different
        $this->scheduling_settings->setCurrentThreshold(12);
        $this->assertEquals(12, $this->scheduling_settings->currentThreshold());
    }




    public function testIsActive()
    {
        //first assert default is set
        $this->assertFalse($this->scheduling_settings->isActive());

        //set to different
        $this->scheduling_settings->setIsActive(true);
        $this->assertTrue($this->scheduling_settings->isActive());
    }
}