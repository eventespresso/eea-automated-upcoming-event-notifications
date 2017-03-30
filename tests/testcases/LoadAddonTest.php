<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');


/**
 * Test class for espresso_addon_skeleton.php
 *
 * @since         1.0.0
 * @package       EventEspresso\AutomatedUpcomingEventNotificaiton
 * @subpackage    tests
 */
class LoadAddonTest extends EE_UnitTestCase
{

    /**
     * Tests the loading of the main file
     *
     * @since 1.0.0
     */
    function test_loadingAddon()
    {
        $this->assertEquals(
            has_action(
                'AHEE__EE_System__load_espresso_addons',
                'load_espresso_automated_upcoming_event_notification'
            ),
            10
        );
        $this->assertTrue(class_exists('EE_Automated_Upcoming_Event_Notification'));
    }
}
