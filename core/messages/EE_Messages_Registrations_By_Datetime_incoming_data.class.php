<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');


/**
 * This prepares data for message types that send messages for multiple registrations that have access to a specific
 * datetime.  This datahandler is required usage for the specific_datetimes messages shortcode library.
 *
 * @package    EE Automated Upcoming Event Notifications
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0
 */
class EE_Messages_Registrations_By_Datetime_incoming_data extends EE_Messages_Registrations_incoming_data
{
    /**
     * @var EE_Datetime
     */
    public $specific_datetime;



    public function __construct($data = array())
    {
        if(! is_array($data) || ! reset($data) instanceof EE_Datetime) {
            throw new EE_Error(
                sprintf(
                    esc_html__(
                        'The %s class expects an array with the first item being an instance of EE_Datetime.',
                        'event_espresso'
                    ),
                    __CLASS__
                )
            );
        }
        $this->specific_datetime = reset($data);
        parent::__construct($data[1]);
    }


    /**
     * Returns database safe representation of the data for storage in the db.
     *
     * @param \EE_Datetime       $datetime
     * @param \EE_Registration[] $registrations
     * @return array
     */
    public static function convert_data_for_persistent_storage(EE_Datetime $datetime, $registrations)
    {
        $registrations =  parent::convert_data_for_persistent_storage($registrations);
        return array($datetime->ID(), $registrations);
    }


    /**
     * Receives an array that was prepared by convert_data_for_persistent_storage for the data and converts it back to
     * the formats expected when instantiating this class.
     *
     * @param array $data
     * @return array
     */
    public static function convert_data_from_persistent_storage($data)
    {
        $registrations = is_array($data) && isset($data[1]) ? $data[1] : array();
        $registrations = parent::convert_data_from_persistent_storage($registrations);
        $specific_datetime = is_array($data) && isset($data[0]) && $data[0] instanceof EE_Datetime
            ? EEM_Datetime::instance()->get_one_by_ID($data[0])
            : null;
        return array($specific_datetime, $registrations);
    }
}