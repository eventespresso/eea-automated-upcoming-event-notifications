<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class EE_Messages_Email_Automate_Upcoming_Datetime_Validator extends EE_Messages_Validator
{
    public function __construct($fields, $context)
    {
        $this->_m_name  = 'email';
        $this->_mt_name = 'automate_upcoming_datetime';
        parent::__construct($fields, $context);
    }


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
            $this->_valid_shortcodes_modifier[$this->_context]['event_list'] = array(
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