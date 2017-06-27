<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\includes;

use EE_UnitTestCase;
use EE_Event;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;
use EEM_Registration;
use EE_Registration;
use EEM_Message_Template_Group;
use EE_Message_Template_Group;
use EEH_MSG_Template;
use DateTime;
use DateTimeZone;
use EE_Datetime;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;

class AddonTestCase extends EE_UnitTestCase
{

    /**
     * @var  array  (indexed by message type).
     */
    protected $message_template_groups;


    /**
     * @var EE_Event[]
     */
    protected $events;


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
    }


    public function tearDown()
    {
        parent::tearDown();
        update_option('timezone_string', $this->original_timezone_string);
        $this->original_timezone_string = '';
        $this->events                         = null;
        $this->message_template_groups = null;
    }



    /**
     * Helper method to return registrations that have registrations recorded as being processed.
     * @return EE_Registration[]
     */
    protected function getRegistrationsProcessed($id_ref)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Registration::instance()->get_all(
            array(
                array(
                    'Extra_Meta.EXM_key' => Constants::REGISTRATION_TRACKER_PREFIX . $id_ref,
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
    protected function setUpGroupsForTest()
    {
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

        //change the threshold on all groups to be for 5 days
        foreach ($all_groups as $group_type => $groups) {
            foreach ($groups as $group) {
                $scheduling_settings = new SchedulingSettings($group);
                $scheduling_settings->setCurrentThreshold(5);
            }
        }

        return $all_groups;
    }


    /**
     * Just sets up some events with datetimes tickets and registrations
     * @return EE_Event[]
     */
    protected function getEventsForTestingWith()
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
    protected function setOneDatetimeOnEventsToDate(DateTime $date, $how_many_events = 1)
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
    protected function setOneDateTimeOnEventToGivenDate(DateTime $date, $message_type, $has_custom = false)
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