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
        /**
         * is there a datetime
         */
        if (! is_array($data) || ! reset($data) instanceof EE_Datetime) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__(
                        'The %1$s class expects an array with the first item being an instance of %2$s.',
                        'event_espresso'
                    ),
                    __CLASS__,
                    'EE_Datetime'
                )
            );
        }

        /**
         * is there a registration
         */
        if ((is_array($data[1]) && ! reset($data[1]) instanceof EE_Registration)
             || ! $data[1] instanceof EE_Registration
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__(
                        'The %1$s class expects an array with the second item either being an instance of %2$s'
                        . ' or an array of %2$s objects.',
                        'event_espresso'
                    ),
                    __CLASS__,
                    'EE_Registration'
                )
            );
        }
        parent::__construct($data);
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


    /**
     * Setup the data
     * Sets up the expected data object for the messages prep using incoming datetime and registration objects.
     */
    protected function _setup_data()
    {
        $data = $this->data();
        $this->specific_datetime = $data[0];

        //assign registrations
        $this->reg_objs = is_array($data[1]) ? $data[1] : array();

        //if the incoming registration value is not in an array format then set that as the reg_obj.  Then we get the
        //matching registrations linked to the same datetime as this registration and assign that to the reg_objs property.
        if (empty($this->reg_objs)) {
            $this->reg_obj = $data[1];
            $this->reg_objs = EEM_Registration::instance()->get_all(
                array(
                    array(
                        'Ticket.Datetime.DTT_ID' => $this->specific_datetime->ID()
                    ),
                    'default_where_conditions' => EEM_Base::default_where_conditions_this_only
                )
            );
        }

        //now set the transaction object if possible
        $this->txn = $this->reg_obj instanceof EE_Transaction
            ? $this->reg_obj->transaction()
            : $this->_maybe_get_transaction();

        $this->_assemble_data();
    }
}