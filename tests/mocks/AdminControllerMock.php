<?php

namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\Controller;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\request\Request;
use EventEspresso\core\services\request\RequestInterface;
use InvalidArgumentException;

class AdminControllerMock extends Controller
{

    /**
     * @var RequestInterface
     */
    private $mocked_request;

    /**
     * AdminControllerMock constructor.
     * Note: By default this will call the parent constructor.  However, for testing methods in isolation without running
     * the usual construction setup, you can avoid calling parent constructor.
     *
     * @param bool $call_parent Whether to call parent constructor or not.
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function __construct($call_parent = true)
    {
        if ($call_parent === true) {
            $this->maybeSetRequest();
            parent::__construct($this->mocked_request);
        }
    }

    /**
     * Use this to test the `canLoad` method
     * Use the WPUnitTestCase::goto method to simulate a request before calling this method.
     *
     * @return bool
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
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
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws \DomainException
     * @throws \LogicException
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
        $this->mocked_request = $this->mocked_request instanceof RequestInterface
            ? $this->mocked_request
            : new Request($_GET, $_POST, $_COOKIE, $_SERVER);
        $this->request = $this->mocked_request;
    }
}