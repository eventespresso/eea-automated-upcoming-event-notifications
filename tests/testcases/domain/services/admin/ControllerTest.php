<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\AdminControllerMock;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;

/**
 * AdminControllerTest
 * Tests for the EventEspresso\AutomateUpcomingEventNotifications\core\messages\admin\Controller class.
 *
 * @package EventEspresso\AutomateUpcomingEventNotifications
 * @subpackage tests
 * @author  Darren Ethier
 * @since   1.0.0
 * @group admin_controller
 */
class ControllerTest extends EE_UnitTestCase
{

    /**
     * @var AdminControllerMock
     */
    private $admin_controller_mock;


    public function setUp()
    {
        parent::setUp();
        $this->admin_controller_mock = new AdminControllerMock(false);
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->admin_controller_mock = null;
    }


    /**
     * Helper to return a message template group with the given message type for testing.
     *
     * @param string $message_type
     * @return EE_Message_Template_Group
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected function messageTemplateGroup($message_type)
    {
        $message_template_group = EE_Message_Template_Group::new_instance(
            array(
                'MTP_message_type' => $message_type
            )
        );
        $message_template_group->save();
        return $message_template_group;
    }


    public function testCanloadWrongPage()
    {
        //test going to some different page.
        $this->go_to(
            add_query_arg(
                array(
                    'page' => 'some_wrong_page'
                ),
                admin_url()
            )
        );
        $this->assertFalse($this->admin_controller_mock->canLoad());
    }


    public function testCanLoadRightPageWrongAction()
    {
        $this->go_to(
            add_query_arg(
                array(
                    'page' => 'espresso_messages',
                    'action' => 'edit_event'
                ),
                admin_url()
            )
        );
        $this->assertFalse($this->admin_controller_mock->canLoad());
    }


    public function testCanLoadRightPageRightActionNoMessageTemplateGroup()
    {
        $this->go_to(
            add_query_arg(
                array(
                    'page' => 'espresso_messages',
                    'action' => 'edit_message_template'
                ),
                admin_url()
            )
        );
        $this->assertFalse($this->admin_controller_mock->canLoad());
    }


    public function testCanLoadRightPageRightActionWrongMessageTemplateGroup()
    {
        $message_template_group = $this->messageTemplateGroup('registration');
        $this->go_to(
            add_query_arg(
                array(
                    'page' => 'espresso_messages',
                    'action' => 'edit_message_template',
                    'id' => $message_template_group->ID()
                ),
                admin_url()
            )
        );
        $this->assertFalse($this->admin_controller_mock->canLoad());
    }


    public function testCanLoadIsValid()
    {
        $message_template_group = $this->messageTemplateGroup('automate_upcoming_datetime');
        $this->go_to(
            add_query_arg(
                array(
                    'page' => 'espresso_messages',
                    'action' => 'update_message_template',
                    'id' => $message_template_group->ID()
                ),
                admin_url()
            )
        );
        $this->assertTrue($this->admin_controller_mock->canLoad());
    }


    public function testIsDisplay()
    {
        //first test when it should be display
        $this->assertTrue($this->admin_controller_mock->isDisplay());
    }

    
    public function testIsDisplayFail()
    {
        $this->go_to(
            add_query_arg(
                array('noheader' => true),
                admin_url()
            )
        );
        $this->assertFalse($this->admin_controller_mock->isDisplay());
    }

}