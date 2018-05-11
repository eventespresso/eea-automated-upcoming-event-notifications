<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * EE_Messages_Email_Automate_Upcoming_Event_Validator
 * Validator for email messenger and automate_upcoming_event message type.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EE_Messages_Email_Automate_Upcoming_Event_Validator extends EE_Messages_Validator
{
    /**
     * EE_Messages_Email_Automate_Upcoming_Event_Validator constructor.
     *
     * @param array  $fields
     * @param string $context
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function __construct($fields, $context)
    {
        $this->_m_name  = 'email';
        $this->_mt_name = Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT;
        parent::__construct($fields, $context);
    }


    /**
     * For modifying validator configuration.
     */
    public function _modify_validator()
    {
        $new_config               = $this->_messenger->get_validator_config();
        $new_config['event_list'] = array(
            'shortcodes' => array(
                'event',
                'attendee_list',
                'ticket_list',
                'datetime_list',
                'venue',
                'organization',
                'event_author',
                'recipient_details',
                'recipient_list',
            ),
            'required'   => array('[EVENT_LIST]'),
        );
        $this->_messenger->set_validator_config($new_config);

        if ($this->_context !== 'admin') {
            $this->_valid_shortcodes_modifier[ $this->_context ]['event_list'] = array(
                'event',
                'attendee_list',
                'ticket_list',
                'datetime_list',
                'venue',
                'organization',
                'event_author',
                'recipient_details',
                'recipient_list',
            );
        }

        $this->_specific_shortcode_excludes['content'] = array('[DISPLAY_PDF_URL]', '[DISPLAY_PDF_BUTTON]');
    }
}
