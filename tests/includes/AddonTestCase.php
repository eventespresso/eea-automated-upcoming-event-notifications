<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\includes;

use EE_Attendee;
use EE_Ticket;
use EE_Transaction;
use EE_UnitTestCase;
use EE_Event;
use EEH_Line_Item;
use EEM_Datetime;
use EEM_Event;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
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
     * Caches the global message template group id when its generated for upcoming event message template group.
     * @var int
     */
    protected $global_event_message_template_group_id = 0;


    /**
     * Caches the global message template group id when its generated for upcoming datetime message template group.
     * @var int
     */
    protected $global_datetime_message_template_group_id = 0;


    /**
     * Caches the custom upcoming event message template group id when its generated.
     * @var int
     */
    protected $custom_event_message_template_group_id = 0;


    /**
     * Caches the custom upcoming datetime message template group id when its generated.
     * @var int
     */
    protected $custom_datetime_message_template_group_id = 0;


    /**
     * Used when creating attendees between each test to give them unique info.
     * @var int
     */
    protected $attendee_counter = 0;


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
        $this->global_event_message_template_group_id = 0;
        $this->global_datetime_message_template_group_id = 0;
        $this->custom_datetime_message_template_group_id = 0;
        $this->custom_event_message_template_group_id = 0;
    }


    /**
     * Helper method to return registrations that have registrations recorded as being processed.
     *
     * @param string $context
     * @return EE_Datetime[]
     */
    protected function getEventsProcessed($context = 'attendee')
    {
        $meta_key_prefix = $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Event::instance()->get_all(
            array(
                array(
                    'Extra_Meta.EXM_key' => $meta_key_prefix,
                ),
            )
        );
    }


    /**
     * Helper method to return registrations that have registrations recorded as being processed.
     *
     * @param string $context
     * @return EE_Datetime[]
     */
    protected function getDatetimesProcessed($context = 'attendee')
    {
        $meta_key_prefix = $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Datetime::instance()->get_all(
            array(
                array(
                    'Extra_Meta.EXM_key' => $meta_key_prefix,
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
        $this->global_event_message_template_group_id = $groups['event']->ID();
        $this->global_datetime_message_template_group_id = $groups['datetime']->ID();
        $all_groups['datetime'][] = $groups['datetime'];
        $all_groups['event'][] = $groups['event'];
        //We'll attach the first event to custom groups.  The other two will be implicitly left attached to the global
        //group.
        /** @var EE_Event $first_event */
        $first_event = reset($this->events);
        //let's change the title for the first event so we have a known value to search for in message templates.
        $first_event->set_name('Event attached to Custom Group.');
        $first_event->save();
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
            $this->{'custom_' . $group_type . '_message_template_group_id'} = $custom_group->ID();
            $custom_group->_add_relation_to($first_event, 'Event');
            $custom_group->save();
            $all_groups[$group_type][] = $custom_group;
        }

        //change the threshold on all groups to be for 5 days
        foreach ($all_groups as $group_type => $groups) {
            foreach ($groups as $group) {
                $scheduling_settings = new SchedulingSettings($group);
                $scheduling_settings->setCurrentThreshold(5, 'admin');
                $scheduling_settings->setCurrentThreshold(5, 'attendee');
            }
        }
        return $all_groups;
    }


    /**
     * Just sets up some events with datetimes tickets and registrations
     *
     * @return EE_Event[]
     * @throws \EE_Error
     */
    protected function getEventsForTestingWith()
    {
        $events = $this->factory->event->create_many(3, array('status' => 'publish'));
        //set one of the events to sold out to ensure sold out events are included.
        $sold_out_event = reset($events);
        $sold_out_event->set_status(EEM_Event::sold_out);
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
        //add free price to each ticke
        /** @var EE_Ticket $ticket */
        foreach ($tickets as $ticket) {
            $price = $this->new_model_obj_with_dependencies(
                'Price',
                array('PRC_amount' => 0)
            );
            $ticket->_add_relation_to($price, 'Price');
            $ticket->save();
        }

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
            // In order to test properly and reproduce the live environment, it's important that the event
            // be attached to another message template group.
            // See https://github.com/eventespresso/eea-automated-upcoming-event-notifications/issues/14#issuecomment-550464526
            $message_templates = EEM_Message_Template_Group::instance()->get_global_message_template_by_m_and_mt(
                'email',
                'payment'
            );
            $message_template = reset($message_templates);
            $event->_add_relation_to($message_template,'Message_Template_Group');
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
            //set a default for the event which is our fallback if $has_custom is true there is no event
            $event = $event_to_check;
            //attempt to get a custom message template group for the event
            $where = array(
                'Event.EVT_ID' => $event_to_check->ID(),
                'MTP_message_type' => $message_type,
                'MTP_messenger' => 'email',
            );
            $message_template_group = EEM_Message_Template_Group::instance()->get_one(array($where));
            if ($has_custom && $message_template_group instanceof EE_Message_Template_Group) {
                $event = $event_to_check;
                break;
            }

            //if we are not looking for an event added to a custom group and our query didn't return a group, then we
            //can just break because we have our event.
            if (! $has_custom && ! $message_template_group instanceof EE_Message_Template_Group) {
                break;
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



    /**
     * Set up a transaction for a specific event (note we already have registrations setup on our events (and attached
     * to tickets) so we just need to make sure that we have the Attendee object set up for all the registrations and
     * take care of setting up the transaction (with line items etc).
     * @param array $specific_events
     */
    protected function setTransactionForEvents(array $specific_events = array())
    {
        $events = $specific_events
            ? $specific_events
            : $this->events;
        /** @var EE_Event $event */
        foreach ($events as $event) {
            /** @var EE_Datetime $datetime */
            foreach ($event->datetimes() as $datetime) {
                /** @var EE_Ticket $ticket */
                foreach ($datetime->tickets() as $ticket) {
                    $transaction = $this->new_model_obj_with_dependencies('Transaction', array('TXN_paid' => 0));
                    $registrations = EEM_Registration::instance()->get_all(array(array('TKT_ID' => $ticket->ID())));
                    $this->attachAnAttendeeAndTransactionToRegistrations($registrations, $transaction);
                    $total_line_item = EEH_Line_Item::create_total_line_item($transaction->ID());
                    $total_line_item->save_this_and_descendants_to_txn($transaction->ID());
                    EEH_Line_Item::add_ticket_purchase($total_line_item, $ticket, 1);
                    $transaction->set_total($total_line_item->total());
                    $transaction->save();
                }
            }
        }
    }


    /**
     * Generates an $attendee to add to registrations and also attaches the given transaction to the given registrations.
     *
     * @param EE_Registration[] $registrations
     * @param EE_Transaction    $transaction
     * @throws \EE_Error
     */
    protected function attachAnAttendeeAndTransactionToRegistrations(array $registrations, EE_Transaction $transaction)
    {
        $attendee = EE_Attendee::new_instance(
            array(
                'ATT_fname' => 'Dude' . $this->attendee_counter,
                'ATT_lname' => 'Guy',
                'ATT_email' => 'dude' . $this->attendee_counter . 'guy@example.org'
            )
        );
        $attendee->save();
        foreach ($registrations as $registration) {
            $registration->_add_relation_to($attendee, 'Attendee');
            $registration->_add_relation_to($transaction, 'Transaction');
            $registration->save();
        }
    }
}
