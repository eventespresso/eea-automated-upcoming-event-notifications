<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * EE_Automate_Upcoming_Event_message_type
 * Message type for automated upcoming event notifications.
 * On a daily cron schedule, this message type will grab all the events where the earliest datetime is within the
 * threshold set by the user (i.e. within x days), then send a notification based on the template generated for this
 * message type.  No matter how many datetimes are present on the event, the notification for this message type is only
 * sent ONCE.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EE_Automate_Upcoming_Event_message_type extends EE_Registration_Base_message_type
{

    public function __construct()
    {
        $this->name              = Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT;
        $this->description       = esc_html__(
            'This message type automates sending messages to registrations for an upcoming event. Messages are sent at the threshold you define (eg 3 days before) prior to the earliest datetime attached to the event.  Other datetimes on the event have no bearing on when this message type is triggered. Messages for this message type are sent to approved registrations and are only triggered for upcoming and/or sold out, and published upcoming events.',
            'event_espresso'
        );
        $this->label             = array(
            'singular' => esc_html__('automated upcoming event notification', 'event_espresso'),
            'plural'   => esc_html__('automated upcoming event notifications', 'event_espresso'),
        );
        $this->_master_templates = array(
            'email' => 'registration',
        );
        parent::__construct();
    }

    /**
     * This sets up the contexts associated with the message_type
     */
    protected function _set_contexts()
    {
        $this->_context_label = array(
            'label'       => esc_html__('recipient', 'event_espresso'),
            'plural'      => esc_html__('recipients', 'event_espresso'),
            'description' => esc_html__(
                'Recipient\'s are who will receive the message. There is only one message sent per attendee, no matter how many registrations are attached to that attendee.',
                'event_espresso'
            ),
        );
        $this->_contexts      = array(
            'admin'    => array(
                'label'       => esc_html__('Event Admin', 'event_espresso'),
                'description' => esc_html__(
                    'This template will be used to generate the message from the context of Event Administrator (event author).',
                    'event_espresso'
                ),
            ),
            'attendee' => array(
                'label'       => esc_html__('Registrant', 'event_espresso'),
                'description' => esc_html__(
                    'This is the template used to generate the message for the attendee.',
                    'event_espresso'
                ),
            ),
        );
    }


    /**
     * Sets the data handler for this message type.
     */
    protected function _set_data_handler()
    {
        $this->_data_handler   = 'Registrations';
        $this->_single_message = $this->_data instanceof EE_Registration;
    }


    /**
     * This message type's data handler is registrations and it expects an array of registrations.
     *
     * @param string           $context
     * @param \EE_Registration $registration
     * @param int              $id
     * @return EE_Registration[]
     */
    protected function _get_data_for_context($context, EE_Registration $registration, $id)
    {
        return array($registration);
    }


    /**
     * @see parent::get_priority() for documentation.
     * @return int
     */
    public function get_priority()
    {
        return EEM_Message::priority_low;
    }
}
