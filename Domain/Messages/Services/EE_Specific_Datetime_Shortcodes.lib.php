<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');

/**
 * EE_Specific_Datetime_Shortcodes
 * Shortcode library parser.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EE_Specific_Datetime_Shortcodes extends EE_Shortcodes
{

    /**
     * Initializes shortcode props.
     */
    protected function _init_props()
    {
        $this->label       = esc_html__('Specific Datetime Shortcodes', 'event_espresso');
        $this->description = esc_html__(
            'All shortcodes related to a specific datetime being targeted in message sending.',
            'event_espresso'
        );
        $this->_shortcodes = array(
            '[SPECIFIC_DATETIME_START]'    => esc_html__('The start date and time.', 'event_espresso'),
            '[SPECIFIC_DATETIME_END]'      => esc_html__('The end date and time.', 'event_espresso'),
            '[SPECIFIC_DATETIME_TIMEZONE]' => esc_html__('The timezone for the date and time', 'event_espresso'),
            '[SPECIFIC_DATE_START]'        => esc_html__('The datetime start date.', 'event_espresso'),
            '[SPECIFIC_DATE_END]'          => esc_html__('The datetime end date.', 'event_espresso'),
            '[SPECIFIC_TIME_START]'        => esc_html__('The datetime start time.', 'event_espresso'),
            '[SPECIFIC_TIME_END]'          => esc_html__('The datetime end time.', 'event_espresso'),
            '[IF_DATETIME_*]'              => sprintf(
                esc_html__(
                    'A special conditional type shortcode that allows people to have content that is conditional in the templates.  Note correct usage is `%1$sContent specific to when the datetime has an id of 10%2$s`. Note  you can use `preview_show` or `preview_hide` as the value of DTT_ID to simulate conditional behaviour in previews when viewing templates.',
                    'event_espresso'
                ),
                '[IF_DATETIME_* DTT_ID=10]',
                '[/IF_DATETIME_*]'
            ),
        );
    }


    /**
     * Parses incoming shortcode string.
     * @param string $shortcode
     * @return string
     * @throws EE_Error
     */
    protected function _parser($shortcode)
    {
        //we need a specific_datetime to do this
        if (! $this->_message_type instanceof EE_Automate_Upcoming_Datetime_message_type
            || ! $this->_message_type->get_specific_datetime() instanceof EE_Datetime
        ) {
            return '';
        }
        $specific_datetime = $this->_message_type->get_specific_datetime();

        switch ($shortcode) {
            case '[SPECIFIC_DATETIME_START]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_start');
                break;
            case '[SPECIFIC_DATETIME_END]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_end');
                break;

            case '[SPECIFIC_DATETIME_TIMEZONE]':
                return $specific_datetime->get_timezone();
                break;
            case '[SPECIFIC_DATE_START]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_start', get_option('date_format'));
                break;
            case '[SPECIFIC_DATE_END]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_end', get_option('date_format'));
                break;
            case '[SPECIFIC_TIME_START]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_start', get_option('time_format'));
                break;
            case '[SPECIFIC_TIME_END]':
                return $specific_datetime->get_i18n_datetime('DTT_EVT_end', get_option('time_format'));
                break;
            case '[/IF_DATETIME_*]':
                //we never parse the closing tag but leave that to be handled by the conditional parsing logic.
                return $shortcode;
        }

        if (strpos($shortcode, '[IF_DATETIME_*' !== false)) {
            $attrs = $this->_get_shortcode_attrs($shortcode);
            //note for previews we allow the use of a 'preview_show', and 'preview_hide' as the value. So users
            //can see what it will look like.
            $show = isset($attrs['DTT_ID'])
                    && $attrs['DTT_ID'] !== 'preview_hide'
                    && (
                        $specific_datetime->ID() === (int)$attrs['DTT_ID']
                        || $attrs['DTT_ID'] === 'preview_show'
                    );
            $this->_mutate_conditional_block_in_template($shortcode, $show);
        }
        return '';
    }
}
