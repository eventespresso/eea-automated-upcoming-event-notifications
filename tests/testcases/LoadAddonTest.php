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
    public function test_loadingAddon()
    {
        $this->assertTrue(class_exists('EventEspresso\AutomatedUpcomingEventNotifications\domain\AutomatedUpcomingEventNotifications'));
    }
}
