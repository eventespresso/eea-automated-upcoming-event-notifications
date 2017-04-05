<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\TriggerUpcomingDatetimeNotificationsMock;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\TriggerUpcomingEventNotificationsMock;
use EventEspresso\AutomatedUpcomingEventNotifications\core\entities\SchedulingSettings;
use EventEspresso\AutomatedUpcomingEventNotifications\core\tasks\TriggerNotifications;

/**
 * TriggerNotificationsTest
 * This tests the notification trigger process.
 *
 * @package EventEspresso\AutomateUpcomingEventNotifications
 * @subpackage tests
 * @author  Darren Ethier
 * @since   1.0.0
 * @group messages
 * @group triggers
 */
class TriggerNotificationsTest extends EE_UnitTestCase
{

    /**
     * @var TriggerUpcomingEventNotificationsMock
     */
    private $trigger_upcoming_event_mock;

    /**
     * @var TriggerUpcomingDatetimeNotificationsMock
     */
    private $trigger_upcoming_datetime_mock;


    /**
     * @var  array  (indexed by message type).
     */
    private $message_template_groups;


    /**
     * @var EE_Event[]
     */
    private $events;


    /**
     * Used for resetting original timezone string after tests.
     * @var string
     */
    private $original_timezone_string = '';


    public function setUp()
    {
        parent::setUp();
        $this->original_timezone_string = get_option('timezone_string');
        update_option('timezone_string', 'America/New_York');
        $this->message_template_groups = $this->setUpGroupsForTest();
        $this->trigger_upcoming_datetime_mock = new TriggerUpcomingDatetimeNotificationsMock(
            $this->message_template_groups['datetime']
        );
        $this->trigger_upcoming_event_mock = new TriggerUpcomingEventNotificationsMock(
            $this->message_template_groups['event']
        );
    }



    public function tearDown()
    {
        parent::tearDown();
        update_option('timezone_string', $this->original_timezone_string);
        $this->original_timezone_string = '';
        $this->trigger_upcoming_datetime_mock = null;
        $this->trigger_upcoming_event_mock    = null;
        $this->events                         = null;
    }


    /**
     * Verifies after setup that we have the correct groups setup.
     */
    public function testGroupPropertiesAfterSetup()
    {
        //so after our setup we expect 1 global and two custom groups for each trigger class.
        $this->assertCount(2, $this->trigger_upcoming_event_mock->messageTemplateGroups());
        $this->assertcount(2, $this->trigger_upcoming_datetime_mock->messageTemplateGroups());

        //globals
        $this->assertInstanceOf(
            'EE_Message_Template_Group',
            $this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup()
        );
        $this->assertInstanceOf(
            'EE_Message_Template_Group',
            $this->trigger_upcoming_event_mock->globalMessageTemplateGroup()
        );
    }



    public function testGetDataForUpcomingDatetimeMessageType()
    {
        //first lets' make sure that on initial call, there is no data. There shouldn't be because none of the groups
        //have been made active.
        $this->assertEmpty($this->trigger_upcoming_datetime_mock->getData());

        //k now let's activate just the global message template group and set the date for our groups
        $global_mtg_settings = new SchedulingSettings($this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup());
        $global_mtg_settings->setIsActive(true);
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $expected_datetime = $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_datetime'
        );
        //okay so our data should include the expected datetime plus one registration on just the global template group id
        $data = $this->trigger_upcoming_datetime_mock->getData();
        $this->assertCount(1, $data);
        //the key should = that of the global template
        $this->assertEquals($this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup()->ID(), key($data));
        //the value of the global message template group index in the array should just have a count of one.
        $this->assertCount(
            1,
            $data[$this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup()->ID()]
        );

        //if we pop the first array element that should have our expected data.
        $data = array_pop($data[$this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup()->ID()]);
        $this->assertInstanceOf('EE_Datetime', $data[0]);
        $this->assertEquals($expected_datetime->ID(), $data[0]->ID());
        $this->assertCount(1, $data[1]);
        $this->assertInstanceOf('EE_Registration', reset($data[1]));

        //k let's set the Global Message Template Group to active and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_mtg_settings->setIsActive(false);
        $custom_mtg_settings = array();
        foreach ($this->trigger_upcoming_datetime_mock->messageTemplateGroups() as $message_template_group) {
            $custom_mtg_settings[$message_template_group->ID()] = new SchedulingSettings($message_template_group);
            $custom_mtg_settings[$message_template_group->ID()]->setIsActive(true);
        }
        $this->assertEmpty($this->trigger_upcoming_datetime_mock->getData());

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a custom
        //message template group.
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_datetime',
            true
        );
        $global_mtg_settings->setIsActive(true);

        //k this time we expect the global template to have one datetime and registration on it and only one custom message
        //template group with one datetime and registration on it.
        $data = $this->trigger_upcoming_datetime_mock->getData();
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $datetimes_and_registrations) {
            $this->assertCount(1, $datetimes_and_registrations);
            $datetimes_and_registrations = array_pop($datetimes_and_registrations);
            $this->assertInstanceOf('EE_Datetime', $datetimes_and_registrations[0]);
            $this->assertCount(1, $datetimes_and_registrations[1]);
            $this->assertInstanceOf('EE_Registration', reset($datetimes_and_registrations[1]));
        }
    }




    public function testGetDataForUpcomingEventMessageType()
    {
        //first lets' make sure that on initial call, there is no data. There shouldn't be because none of the groups
        //have been made active.
        $this->assertEmpty($this->trigger_upcoming_event_mock->getData());

        //k now let's activate just the global message template group and set the date for our groups
        $global_mtg_settings = new SchedulingSettings($this->trigger_upcoming_event_mock->globalMessageTemplateGroup());
        $global_mtg_settings->setIsActive(true);
        $date_three_days_from_now = new DateTime('now +3 days', new DateTimeZone(get_option('timezone_string')));
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event'
        );
        //okay so our data should include the expected datetime plus one registration on just the global template group id
        $data = $this->trigger_upcoming_event_mock->getData();
        $this->assertCount(1, $data);
        //the key should = that of the global template
        $this->assertEquals($this->trigger_upcoming_event_mock->globalMessageTemplateGroup()->ID(), key($data));
        //the value of the global message template group index in the array should have a count of 3 because the match is
        // against ANY datetime for the event within the threshold.  So that means all three registrations on the event
        // should get returned..
        $this->assertCount(3, $data[$this->trigger_upcoming_event_mock->globalMessageTemplateGroup()->ID()]);
        //if we pop the first array element that should have our expected data.
        $data = array_pop($data[$this->trigger_upcoming_event_mock->globalMessageTemplateGroup()->ID()]);
        $this->assertInstanceOf('EE_Registration', $data);

        //k let's set the Global Message Template Group to active and the custom message template groups to active.
        //We don't expect any data to get returned in this scenario because the event modified is not attached to any of
        //those groups.
        $global_mtg_settings->setIsActive(false);
        $custom_mtg_settings = array();
        foreach ($this->trigger_upcoming_event_mock->messageTemplateGroups() as $message_template_group) {
            $custom_mtg_settings[$message_template_group->ID()] = new SchedulingSettings($message_template_group);
            $custom_mtg_settings[$message_template_group->ID()]->setIsActive(true);
        }
        $this->assertEmpty($this->trigger_upcoming_event_mock->getData());

        //okay now let's set all message template groups active, and then add one more datetime ahead but only on a custom
        //message template group.
        $this->setOneDateTimeOnEventToGivenDate(
            $date_three_days_from_now,
            'automate_upcoming_event',
            true
        );
        $global_mtg_settings->setIsActive(true);

        //k this time we expect the global template to have three registrations on it and only one custom message
        //template group with three registrations on it.
        $data = $this->trigger_upcoming_event_mock->getData();
        $this->assertCount(2, $data);
        foreach ($data as $message_template_group_id => $registrations) {
            $this->assertCount(3, $registrations);
            $registrations = array_pop($registrations);
            $this->assertInstanceOf('EE_Registration', $registrations);
        }
    }



    public function testTriggerUpcomingDatetimeProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->trigger_upcoming_datetime_mock->process($this->trigger_upcoming_datetime_mock->getData());
        //for each of our datetimes there should be no registrations processed.
        /** @var EE_Event $event */
        foreach ($this->events as $event) {
            foreach ($event->datetimes() as $datetime) {
                $this->assertEmpty($this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
            }
        }

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $datetimes = $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->trigger_upcoming_datetime_mock->process($this->trigger_upcoming_datetime_mock->getData());
        foreach ($datetimes as $datetime) {
            $this->assertEmpty($this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
        }

        //now let's set the message template groups to active
        $global_settings = new SchedulingSettings($this->trigger_upcoming_datetime_mock->globalMessageTemplateGroup());
        $global_settings->setIsActive(true);

        foreach ($this->trigger_upcoming_datetime_mock->messageTemplateGroups() as $message_template_group) {
            $settings = new SchedulingSettings($message_template_group);
            $settings->setIsActive(true);
        }
        $this->trigger_upcoming_datetime_mock->process($this->trigger_upcoming_datetime_mock->getData());

        //now process again and there should be one registration marked processed for each datetime
        foreach ($datetimes as $datetime) {
            $this->assertCount(1, $this->getRegistrationsProcessed('DTT_' . $datetime->ID()));
        }
    }



    public function testTriggerUpcomingEventNotificationProcess()
    {
        //first if there is no upcoming datetime, then there should be no registrations processed.
        $this->trigger_upcoming_event_mock->process($this->trigger_upcoming_event_mock->getData());
        /** @var EE_Event $event */
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));

        //setting dates to within the threshold but NOT setting any message template groups active.  Should still result
        //in no registrations processed.
        $four_days_from_now = new DateTime('now +4 days', new DateTimezone(get_option('timezone_string')));
        $this->setOneDatetimeOnEventsToDate($four_days_from_now, 2);
        $this->trigger_upcoming_event_mock->process($this->trigger_upcoming_event_mock->getData());
        $this->assertEmpty($this->getRegistrationsProcessed('EVT'));

        //now let's set the message template groups to active
        $global_settings = new SchedulingSettings($this->trigger_upcoming_event_mock->globalMessageTemplateGroup());
        $global_settings->setIsActive(true);

        foreach ($this->trigger_upcoming_event_mock->messageTemplateGroups() as $message_template_group) {
            $settings = new SchedulingSettings($message_template_group);
            $settings->setIsActive(true);
        }

        /**
         * The expectation here is because this is an _event_ based notification, if the event has ANY datetime
         * matching the "upcoming" threshold, then all the registrations for that event will get notified (regardless of
         * whether they have access to the date triggering the message or not).  So this means since our expectation data
         * has 3 registrations per event, and our threshold should trigger two events to match, we should get 6
         * registrations returned for our test.
         */
        $this->trigger_upcoming_event_mock->process($this->trigger_upcoming_event_mock->getData());
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));
        //messages triggered should have accumulated a total of 6 registrations
        $this->assertCount(6, $this->trigger_upcoming_event_mock->messages_triggered);


        //The expectation is that each registration will only ever get processed ONCE for an event (so if an event has
        //multiple datetimes, and that registration belonged to multiple datetimes, it would still only get one email sent
        //for that event, this message type, and the threshold given.  In this case triggering processing again should result
        //in no registrations being queued up for sending.
        $this->trigger_upcoming_event_mock->process($this->trigger_upcoming_event_mock->getData());
        //this should still be six.
        $this->assertCount(6, $this->getRegistrationsProcessed('EVT'));

        //this should be empty (meaning no messages processed)
        $this->assertEmpty($this->trigger_upcoming_event_mock->messages_triggered);
    }


    /**
     * Helper method to return registrations that have registrations recorded as being processed.
     * @return EE_Registration[]
     */
    private function getRegistrationsProcessed($id_ref)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Registration::instance()->get_all(
            array(
                array(
                'Extra_Meta.EXM_key' => TriggerNotifications::REGISTRATION_TRACKER_PREFIX . $id_ref,
                ),
            )
        );
    }




    /**
     * Sets up message template groups for test.
     * Note, this also sets up some custom groups attached to events that will be used for tests.
     * This also sets up events that have registrations and datetimes.  Full data relations are not provided, only the
     * stuff needed for the tests are.
     */
    private function setUpGroupsForTest()
    {
        EE_Registry::instance()->load_helper('MSG_Template');
        //first we need some events (to attach our custom groups to).  While we're at it we'll setup some datetimes and
        //registrations too.
        $this->events = $this->getEventsForTestingWith();
        $all_groups = array();

        //now let's get the global groups and clone them for custom groups for our events.  One event will NOT have
        //custom groups applied so we know it gets the global message template.
        /** @var EE_Message_Template_Group $upcoming_datetime_group */
        $groups['datetime'] = EEM_Message_Template_Group::instance()->get_global_message_template_by_m_and_mt(
            'email',
            'automate_upcoming_datetime'
        );
        /** @var EE_Message_Template_Group $upcoming_event_group */
        $groups['event'] = EEM_Message_Template_Group::instance()->get_global_message_template_by_m_and_mt(
            'email',
            'automate_upcoming_event'
        );
        //there should only be ONE global Message_Template_Group so let's verify that.
        $this->assertCount(1, $groups['datetime']);
        $this->assertCount(1, $groups['event']);
        $groups['datetime'] = reset($groups['datetime']);
        $groups['event'] = reset($groups['event']);
        $all_groups['datetime'][] = $groups['datetime'];
        $all_groups['event'][] = $groups['event'];
        //clone and set custom groups and set relations to two events.
        $i = 0;
        foreach ($this->events as $event) {
            if ($i === 2) {
                break;
            }

            /** @var EE_Message_Template_Group $group */
            foreach ($groups as $group_type => $group) {
                $custom_group = EEH_MSG_Template::generate_new_templates(
                    $group->messenger(),
                    array($group->message_type()),
                    $group->ID()
                );
                $custom_group = EEM_Message_Template_Group::instance()->get_one_by_ID(
                    $custom_group[0]['GRP_ID']
                );
                $custom_group->_add_relation_to($event, 'Event');
                $custom_group->save();
                $all_groups[$group_type][] = $custom_group;
            }
            $i++;
        }
        return $all_groups;
    }


    /**
     * Just sets up some events with datetimes tickets and registrations
     * @return EE_Event[]
     */
    private function getEventsForTestingWith()
    {
        $events = $this->factory->event->create_many(3, array('status' => 'publish'));
        //for the purpose of this test, we set all DTT_EVT_start to a time in the past to normalize for tests (meaning
        //the threshold should always fall outside expectations.
        $date = new DateTime('now -1 day', new DateTimeZone(get_option('timezone_string')));
        $datetimes = $this->factory->datetime->create_many(
            9,
            array('DTT_EVT_start' => $date->format('U'))
        );
        $registrations = $this->factory->registration->create_many(
            9,
            array('STS_ID' => EEM_Registration::status_id_approved)
        );
        $tickets = $this->factory->ticket->create_many(9);

        //attach these dudes. We want to end up with:
        //3 Events
        //Each event has 3 datetimes.
        //Each event has 3 tickets with one datetime on each ticket.
        //Each event has ticket has 1 registration.

        /** @var EE_Event $event */
        foreach ($events as $event) {
            $dtt_count = 0;

            /**
             * Setup datetimes on the event
             * @var int $dkey
             * @var EE_Datetime $datetime
             */
            foreach ($datetimes as $dkey => $datetime) {
                //as soon as count == 3 then we break (that means we have three datetimes on each event)
                if ($dtt_count === 3) {
                    break;
                }
                $event->_add_relation_to($datetime, 'Datetime');

                //setup one ticket on each datetime
                $ticket = array_pop($tickets);
                $datetime->_add_relation_to($ticket, 'Ticket');

                //we want one registration on each ticket
                /** @var EE_Registration $registration */
                $registration = array_pop($registrations);
                $registration->_add_relation_to($ticket, 'Ticket');

                //and add the registration to the event too.
                $registration->_add_relation_to($event, 'Event');

                //save
                $datetime->save();
                $registration->save();

                unset($datetimes[$dkey]);
                $dtt_count++;
            }
            //save event
            $event->save();
        }
        return $events;
    }


    /**
     * This helper assists with setting the DTT_EVT_start value on one Datetime for the given number of events
     * in the event property.  T
     * Used for setting up expectations.
     * Returns an array of datetimes that were affected (for any expectation tests)
     *
     * @param DateTime $date
     * @param string   $message_type
     * @param int      $how_many_events
     * @return \EE_Datetime[]
     */
    private function setOneDatetimeOnEventsToDate(DateTime $date, $how_many_events = 1)
    {
        $how_many_events = min($how_many_events, 3);
        $datetimes = array();

        $i = 0;
        foreach ($this->events as $event) {
            if ($i === $how_many_events) {
                break;
            }
            //get first related datetime.
            $datetime = $event->first_datetime();
            $datetime->set('DTT_EVT_start', $date->format('U'));
            $datetime->save();
            $datetimes[] = $datetime;
            $i++;
        }
        //assert the right number of datetimes.
        $this->assertCount($how_many_events, $datetimes);
        return $datetimes;
    }


    /**
     * This will set a datetime on an event that is either attached to a custom message template group or not.
     *
     * @param \DateTime $date
     * @param           $message_type
     * @param bool      $has_custom
     * @return EE_Datetime  The datetime that was modified (which can be used to retrieve the event for expectations)
     */
    private function setOneDateTimeOnEventToGivenDate(DateTime $date, $message_type, $has_custom = false)
    {
        //if $has_custom that means we only pick an event that is attached to custom message types.
        $datetime = null;
        $event = null;

        //we loop through our known events for and prepare a query depending on the incoming arguments to get an event
        //matching the initial set of conditions.
        /** @var EE_Event $event */
        foreach ($this->events as $event_to_check) {
            if ($has_custom) {
                $where = array(
                    'Event.EVT_ID' => $event_to_check->ID(),
                    'MTP_message_type' => $message_type,
                    'MTP_messenger' => 'email',
                );
            } else {
                $where = array(
                    'MTP_message_type' => $message_type,
                    'MTP_messenger' => 'email',
                    'MTP_is_global' => 1,
                );
            }
            //attempt to get the message template group
            $message_template_group = EEM_Message_Template_Group::instance()->get_one(array($where));
            //if we have a group, great let's get out
            if ($message_template_group) {
                $event = $event_to_check;
            }
        }

        //k we should have an event now, so let's grab a datetime from it set the date and then return the datetime.
        $this->assertInstanceOf('EE_Event', $event);
        /** @var EE_Datetime $datetime */
        $datetime = $event->get_first_related('Datetime');
        $datetime->set('DTT_EVT_start', $date->format('U'));
        $datetime->save();
        return $datetime;
    }
}