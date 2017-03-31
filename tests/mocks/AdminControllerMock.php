<?php

namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\core\messages\admin\Controller;
use EE_Request;
use EE_Request_Handler;

class AdminControllerMock extends Controller
{
    /**
     * AdminControllerMock constructor.
     * Note: By default this will call the parent constructor.  However, for testing methods in isolation without running
     * the usual construction setup, you can avoid calling parent constructor.
     * @param bool $call_parent  Whether to call parent constructor or not.
     */
    public function __construct($call_parent = true)
    {
        if ($call_parent === true) {
            parent::__construct();
        }
    }

    /**
     * Use this to test the `canLoad` method
     * Use the WPUnitTestCase::goto method to simulate a request before calling this method.
     * @return bool
     */
    public function canLoad()
    {
        $this->maybeSetRequest();
        return parent::canLoad();
    }


    /**
     * Mocks the `isDisplay` protected method
     * Use the WPUnitTestCase::goto method to simulate a request before calling this method.
     * @return bool
     */
    public function isDisplay()
    {
        $this->maybeSetRequest();
        return parent::isDisplay();
    }


    /**
     * Mocks the schedulingMetabox method.
     * Use the WPUnitTestCase::goto method to simulate a request before calling this method.
     * This also covers the following methods:
     * - messageTemplateGroup
     * - schedulingForm
     * @return string
     */
    public function schedulingMetabox()
    {
        $this->maybeSetRequest();
        ob_start();
        parent::schedulingMetabox();
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }


    /**
     * Since the $request property is usually set via the parent constructor, we're just using this to take care of setting
     * it for our mocks and correctly consider whether its already been set or not.
     * Note: Use the WPUnitTestCase::goto method to simulate a request before calling this method (or before calling
     * parentConstruct)
     */
    private function maybeSetRequest()
    {
        $this->request = $this->request instanceof EE_Request_Handler
            ? $this->request
            : new EE_Request_Handler(new EE_Request($_GET, $_POST, $_COOKIE));
    }
}