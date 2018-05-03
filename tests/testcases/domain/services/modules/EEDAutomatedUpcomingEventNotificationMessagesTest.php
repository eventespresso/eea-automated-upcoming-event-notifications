<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed.');

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\EEDAutomatedUpcomingEventNotificationMessagesMock;

/**
 * EEDAutomatedUpcomingEventNotificationsTest
 * Test for EED_Automated_Upcoming_Event_Notifications module
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage tests
 * @author     Darren Ethier
 * @since      1.0.0
 * @group      messages
 * @group      modules
 */
class EEDAutomatedUpcomingEventNotificationMessagesTest extends EE_UnitTestCase
{
    public function tearDown()
    {
        parent::tearDown();
        EEDAutomatedUpcomingEventNotificationMessagesMock::reset();
    }


    public function setUp()
    {
        parent::setUp();
        //we need to make sure our message types are enabled for the tests in here.
        //we're only doing attendee, so let's enable the attendee context.
        $message_types = EEM_Message_Template_Group::instance()->get_all(
            array(
                array(
                    'MTP_message_type' => array('IN', array(
                        Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT,
                        Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME,
                    )),
                ),
            )
        );
        /** @var EE_Message_Template_Group $message_type */
        foreach ($message_types as $message_type) {
            $message_type->activate_context('attendee');
        }
    }


    /**
     * This returns test data for testing the message types.
     *
     * @param $message_type  What message type is being tested.
     * @return array   data for the message type being tested.
     * @throws PHPUnit_Framework_Exception
     */
    private function getRegistrationsAndDatetimeForTest($message_type)
    {
        $registrations = $this->factory->registration_chained->create_many(3);
        //get one registration
        $registration = reset($registrations);
        $this->assertInstanceOf('EE_Registration', $registration);

        //get the datetime and we'll use that as our datetime.
        $datetime = $registration->ticket()->get_first_related('Datetime');
        return $message_type === 'automate_upcoming_datetime'
            ? array($datetime, $registrations)
            : $registrations;
    }


    public function testMessageTypesAreActiveByDefault()
    {
        EE_Registry::instance()->load_helper('MSG_Template');
        $this->assertTrue(EEH_MSG_Template::is_mt_active('automate_upcoming_datetime'));
        $this->assertTrue(EEH_MSG_Template::is_mt_active('automate_upcoming_event'));
    }


    public function testTriggerMessagesInvalidMessageType()
    {
        //shouldn't allow because its the wrong message type
        EEDAutomatedUpcomingEventNotificationMessagesMock::prep_and_queue_messages('registration', array(),'admin');
        //processor should not be set.
        $this->assertNull(EEDAutomatedUpcomingEventNotificationMessagesMock::getProcessor());
    }


    public function testTriggerUpcomingDatetimeMessages()
    {
        /** @var $datetime EE_Datetime */
        /** @var $registrations EE_Registration[] */
        /** @var $messages EE_Message[] */
        list($datetime, $registrations, $messages) = $this->initializeTestForUpcomingMessagesTest(
            'automate_upcoming_datetime'
        );

        $registration = reset($registrations);

        //map of actual to expectations for contexts
        $test_map = array(
            'attendee' => array(
                'to'      => $registration->attendee()->email(),
                'from'    => 'admin@example.org',
                'subject' => 'Upcoming Datetime Reminder',
            ),
        );

        foreach ($test_map as $context => $methods_to_test) {
            foreach ($methods_to_test as $method_to_test => $expectation) {
                $this->assertEquals(
                    $expectation,
                    $messages[$context]->$method_to_test(),
                    sprintf('Testing "%s" method', $method_to_test)
                );
            }
        }

        $expected_content = htmlentities(
            "We're reaching out to remind you of an upcoming event you registered for on our website. "
            . 'You have access to this event on '
            . $datetime->get_i18n_datetime('DTT_EVT_start')
            . ' - '
            . $datetime->get_i18n_datetime('DTT_EVT_end')
            . ". Here's a copy of your registration details:",
            ENT_QUOTES
        );

        //test content outputs for attendee
        //datetime in content
        $this->assertNotFalse(
            strpos(
                $messages['attendee']->content(),
                $expected_content
            )
        );

        //number of tickets in content
        $this->assertEquals(
            1,
            substr_count(
                $messages['attendee']->content(),
                $registration->ticket()->name()
            )
        );

        //registration format
        $registration_codes_string = array();
        /** @var EE_Registration $reg */
        foreach ($registrations as $reg) {
            $registration_codes_string[] = $reg->reg_code();
        }
        $registration_codes_string = implode(', ', $registration_codes_string);
        $this->assertNotFalse(strpos($messages['attendee']->content(), $registration_codes_string));
    }


    public function testTriggerUpcomingEventMessages()
    {
        /** @var $datetime EE_Datetime */
        /** @var $registrations EE_Registration[] */
        /** @var $messages EE_Message[] */
        list($datetime, $registrations, $messages) = $this->initializeTestForUpcomingMessagesTest(
            'automate_upcoming_event'
        );

        $registration = reset($registrations);

        //map of actual to expectations for contexts
        $test_map = array(
            'attendee' => array(
                'to'      => $registration->attendee()->email(),
                'from'    => 'admin@example.org',
                'subject' => 'Upcoming Event Reminder',
            ),
        );

        foreach ($test_map as $context => $methods_to_test) {
            foreach ($methods_to_test as $method_to_test => $expectation) {
                $this->assertEquals(
                    $expectation,
                    $messages[$context]->$method_to_test(),
                    sprintf('Testing "%s" method', $method_to_test)
                );
            }
        }

        $expected_content = htmlentities(
            'We\'re reaching out to remind you of upcoming events you registered for on our website. '
            . 'Here\'s a copy of your registration details:',
            ENT_QUOTES
        );
        //test content outputs for attendee
        //datetime in content
        $this->assertNotFalse(
            strpos(
                $messages['attendee']->content(),
                $expected_content
            )
        );

        //number of tickets in content
        $this->assertEquals(
            1,
            substr_count(
                $messages['attendee']->content(),
                $registration->ticket()->name()
            )
        );

        //registration format
        $registration_codes_string = array();
        /** @var EE_Registration $reg */
        foreach ($registrations as $reg) {
            $registration_codes_string[] = $reg->reg_code();
        }
        $registration_codes_string = implode(', ', $registration_codes_string);
        $this->assertNotFalse(strpos($messages['attendee']->content(), $registration_codes_string));
    }


    /**
     * Initializing test for upcoming messages test.
     *
     * @param $message_type
     * @return array  array(EE_Datetime, EE_Registration, EE_Message[])
     */
    private function initializeTestForUpcomingMessagesTest($message_type)
    {
        $data_for_testing = $this->getRegistrationsAndDatetimeForTest($message_type);
        EEDAutomatedUpcomingEventNotificationMessagesMock::prep_and_queue_messages(
            $message_type,
            $data_for_testing,
            'attendee'
        );
        $messages_processor = EEDAutomatedUpcomingEventNotificationMessagesMock::getProcessor();
        $this->assertInstanceOf('EE_Messages_Processor', $messages_processor);
        //trigger generation
        $queue = $messages_processor->batch_generate_from_queue();
        $this->assertInstanceOf('EE_Messages_Queue', $queue);
        //should only be one messages, one for the attendee because there was only one attendee for all registrations
        //we didn't process the admin context so that should not be here.
        $this->assertEquals(1, $queue->get_message_repository()->count());

        //get the message from the queue for verification of message generation.
        $queue->get_message_repository()->rewind();
        $contexts = array('attendee');
        $messages = array();
        $i        = 0;
        while ($queue->get_message_repository()->valid()) {
            $messages[$contexts[$i]] = $queue->get_message_repository()->current();
            $queue->get_message_repository()->next();
            $i++;
        }

        $registrations = $message_type === 'automate_upcoming_event'
            ? $data_for_testing
            : $data_for_testing[1];
        $datetime      = $message_type === 'automate_upcoming_datetime'
            ? $data_for_testing[0]
            : null;
        $datetime      = $datetime === null
            ? reset($registrations)->ticket()->get_first_related('Datetime')
            : $datetime;

        return array($datetime, $registrations, $messages);
    }
}



