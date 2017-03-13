<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');

/**
 * Message type for automated upcoming event notifications.
 * On a daily cron schedule, this message type will grab all the events where the earliest datetime is within the
 * threshold set by the user (i.e. within x days), then send a notification based on the template generated for this
 * message type.  No matter how many datetimes are present on the event, the notification for this message type is only
 * sent ONCE.
 *
 */
class EE_Automate_Upcoming_Event_message_type extends EE_Registration_Base_message_type
{

    public function __construct()
    {
        $this->name = 'automate_upcoming_event';
        $this->description = esc_html__(
            'This message type automates sending messages to registrations for an upcoming event.'
            . 'Messages are sent at the threshold you define (eg 3 days before) prior to the earliest datetime attached'
            . ' to the event.  Other datetimes on the event have no bearing on when this message type is triggered.'
            . ' Messages for this message type are sent to approved registrations and are only triggered for upcoming'
            . ' and/or sold out, and published upcoming events.',
            'event_espresso'
        );
        $this->label = array(
            'singular' => esc_html__('automated upcoming event notification', 'event_espresso'),
            'plural' => esc_html__('automated upcoming event notifications', 'event_espresso')
        );
        $this->_master_templates = array(
            'email' => 'registration'
        );
        parent::__construct();
    }

    /**
     * This sets up the contexts associated with the message_type
     *
     * @access  protected
     * @return  void
     */
    protected function _set_contexts()
    {
        $this->_context_label = array(
           'label' => esc_html__('recipient', 'event_espresso'),
           'plural' => esc_html__('recipients', 'event_espresso'),
           'description' => esc_html__(
               'Recipient\'s are who will receive the message. There is only one message sent per attendee, no'
               . ' no matter how many registrations are attached to that attendee.',
               'event_espresso'
           )
        );
        $this->_contexts = array(
            'admin' => array(
                'label' => esc_html__('Event Admin', 'event_espresso'),
                'description' => esc_html__('This template is what event administrators will receive with an approved registration', 'event_espresso')
            ),
            'attendee' => array(
               'label' => esc_html__('Registrant', 'event_espresso'),
               'description' => esc_html__(
                   'This is the template used to generate the message for the attendee.',
                   'event_espresso'
               )
            )
        );
    }


    /**
     * @see parent::get_priority() for documentation.
     * @return int
     */
    public function get_priority() {
        return EEM_Message::priority_low;
    }
}