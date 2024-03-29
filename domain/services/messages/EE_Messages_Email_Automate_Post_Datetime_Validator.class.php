<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * EE_Messages_Email_Automate_Post_Datetime_Validator
 * Shortcode validator for Email messenger and automate_post_datetime message type.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @author  Tony Warwick
 * @since   1.0.6.p
 */
class EE_Messages_Email_Automate_Post_Datetime_Validator extends EE_Messages_Validator
{

    /**
     * EE_Messages_Email_Automate_Post_Datetime_Validator constructor.
     *
     * @param array  $fields
     * @param string $context
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function __construct($fields, $context)
    {
        $this->_m_name  = 'email';
        $this->_mt_name = Domain::MESSAGE_TYPE_AUTOMATE_POST_DATETIME;
        parent::__construct($fields, $context);
    }


    /**
     * Used to modify validation configuration
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
