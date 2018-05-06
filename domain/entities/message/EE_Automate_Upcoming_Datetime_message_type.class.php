<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;

/**
 * EE_Automate_Upcoming_Datetime_message_type
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
class EE_Automate_Upcoming_Datetime_message_type extends EE_Registration_Base_message_type
{

    /**
     * EE_Automate_Upcoming_Datetime_message_type constructor.
     */
    public function __construct()
    {
        $this->name              = Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME;
        $this->description       = esc_html__(
            'This message type automates sending messages to registrations for an upcoming datetime. Messages are sent at the threshold you define (eg 3 days before) prior to a datetime on an event. Messages for this message type are sent to approved registrations and are only triggered for datetimes on upcoming and/or sold out, and published upcoming events. Note that this will send the message for each datetime on the event.',
            'event_espresso'
        );
        $this->label             = array(
            'singular' => esc_html__('automated upcoming datetime notification', 'event_espresso'),
            'plural'   => esc_html__('automated upcoming datetime notifications', 'event_espresso'),
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
     * @see parent::get_priority() for documentation.
     * @return int
     */
    public function get_priority()
    {
        return EEM_Message::priority_low;
    }


    /**
     * Sets the data handler for this message type.
     */
    protected function _set_data_handler()
    {
        $this->_data_handler = 'Registrations_By_Datetime';
        // set whether this is a single message or not.
        if (is_array($this->_data) && isset($this->_data[1]) && ! is_array($this->_data[1])) {
            $this->_single_message = $this->_data[1] instanceof EE_Registration ? true : $this->_single_message;
        }
    }


    /**
     * Used to get the specific datetime if it exists in the internal $data property.
     * Note: this will only return an EE_Datetime after the data has been setup.
     *
     * @return EE_Datetime|null
     */
    public function get_specific_datetime()
    {
        return isset($this->_data->specific_datetime) && $this->_data->specific_datetime instanceof EE_Datetime
            ? $this->_data->specific_datetime
            : null;
    }


    /**
     * Called when loading the view for a specific registration for this message type and registration.
     *
     * @param string           $context
     * @param \EE_Registration $registration
     * @param int              $id In this message type, the ID corresponds to a Datetime.
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public function _get_data_for_context($context, EE_Registration $registration, $id)
    {
        // all contexts require the $id to be set because the data handler for this message type _requires_ a datetime.
        $datetime = EEM_Datetime::instance()->get_one_by_ID($id);
        if (! $datetime instanceof EE_Datetime) {
            throw new InvalidArgumentException(
                esc_html__(
                    'A datetime could not be retrieved for the given id. It is required for this message type.',
                    'event_espresso'
                )
            );
        }

        $registrations = $context === 'admin'
            ? EEM_Registration::instance()->get_all(
                array(
                    array(
                        'Ticket.Datetime.DTT_ID' => $id,
                    ),
                    'default_where_conditions' => EEM_Base::default_where_conditions_this_only,
                )
            )
            // yes this is intentionally not an array.  The format for a single registration view (which is what the
            // registrant context is, is to have just the individual registration as the value.
            : $registration;
        return array($datetime, $registrations);
    }
}
