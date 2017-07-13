<?php
/**
 * Bootstrap for eea-automated-upcoming-event tests
 */
use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(dirname(__DIR__)) . '/event-espresso-core/tests/';
//if still don't have $core_tests_dir, then let's check tmp folder.
if (! is_dir($core_tests_dir)) {
    $core_tests_dir = '/tmp/event-espresso-core/tests/';
}
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EE_AUTOMATED_UPCOMING_EVENT_PLUGIN_DIR', dirname(__DIR__) . '/');
define('EE_AUTOMATED_UPCOMING_EVENT_TESTS_DIR', EE_AUTOMATED_UPCOMING_EVENT_PLUGIN_DIR . 'tests/');


$addon_loader = new AddonLoader(
    EE_AUTOMATED_UPCOMING_EVENT_TESTS_DIR,
    EE_AUTOMATED_UPCOMING_EVENT_PLUGIN_DIR,
    'eea-automated-upcoming-event-notification.php'
);
$addon_loader->init();
$addon_loader->registerPsr4Path(
    array(
        'EventEspresso\AutomateUpcomingEventNotificationsTests' => EE_AUTOMATED_UPCOMING_EVENT_TESTS_DIR
    )
);
