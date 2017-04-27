<?php
use EventEspresso\core\exceptions\EntityNotFoundException;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct script access allowed');


/**
 * This prepares data for message types that send messages for multiple registrations that have access to a specific
 * datetime.  This data handler is required usage for the specific_datetimes messages shortcode library.
 *
 * @package    EE Automated Upcoming Event Notifications
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0
 */
class EE_Messages_Registrations_By_Datetime_incoming_data extends EE_Messages_incoming_data
{
    /**
     * @var EE_Datetime
     */
    public $specific_datetime;


    /**
     * EE_Messages_Registrations_By_Datetime_incoming_data constructor.
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    public function __construct($data = array())
    {
        self::validate_incoming_data($data);
        parent::__construct($data);
    }


    /**
     * Returns database safe representation of the data for storage in the db.
     *
     * @param array $datetime_and_registrations  An array where the first element is a datetime object and the second is
     *                                           an array of registration objects (or single registration object).
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     */
    public static function convert_data_for_persistent_storage($datetime_and_registrations)
    {
        self::validate_incoming_data($datetime_and_registrations);
        //make sure $registrations are an array.
        $datetime_and_registrations[1] = is_array($datetime_and_registrations[1])
            ? $datetime_and_registrations[1]
            : array($datetime_and_registrations[1]);
        $registration_ids              = array_filter(
            array_map(
                function ($registration) {
                    if ($registration instanceof EE_Registration) {
                        return $registration->ID();
                    }
                    return false;
                },
                $datetime_and_registrations[1]
            )
        );
        return array($datetime_and_registrations[0]->ID(), $registration_ids);
    }


    /**
     * Validate incoming data to make sure its the expected format.
     *
     * @param $datetime_and_registrations
     * @throws InvalidArgumentException
     */
    protected static function validate_incoming_data($datetime_and_registrations)
    {
        /**
         * is there a datetime
         */
        if (! (
                $datetime_and_registrations[1] instanceof EE_Registration
                || (
                    is_array($datetime_and_registrations[1])
                    && reset($datetime_and_registrations[1]) instanceof EE_Registration
                )
            )
        ) {
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
        if ((
                is_array($datetime_and_registrations[1])
                && ! reset($datetime_and_registrations[1]) instanceof EE_Registration
            )
            && ! $datetime_and_registrations[1] instanceof EE_Registration
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
    }


    /**
     * Receives an array that was prepared by convert_data_for_persistent_storage for the data and converts it back to
     * the formats expected when instantiating this class.
     *
     * @param array $data
     * @return array
     * @throws EE_Error
     */
    public static function convert_data_from_persistent_storage($data)
    {
        $registrations     = is_array($data) && isset($data[1])
            ? EEM_Registration::instance()->get_all(array(array('REG_ID' => array('IN', $data[1]))))
            : array();
        $specific_datetime = is_array($data) && isset($data[0])
            ? EEM_Datetime::instance()->get_one_by_ID($data[0])
            : null;
        return array($specific_datetime, $registrations);
    }


    /**
     * Setup the data
     * Sets up the expected data object for the messages prep using incoming datetime and registration objects.
     *
     * @throws EE_Error
     * @throws EntityNotFoundException
     */
    protected function _setup_data()
    {
        $data                    = $this->data();
        $this->specific_datetime = $data[0];

        //assign registrations
        $this->reg_objs = is_array($data[1]) ? $data[1] : array();

        //if the incoming registration value is not in an array format then set that as the reg_obj.  Then we get the
        //matching registrations linked to the same datetime as this registration and assign that to the reg_objs property.
        if (empty($this->reg_objs)) {
            $this->reg_obj  = $data[1];
            $this->reg_objs = EEM_Registration::instance()->get_all(
                array(
                    array(
                        'Ticket.Datetime.DTT_ID' => $this->specific_datetime->ID(),
                    ),
                    'default_where_conditions' => EEM_Base::default_where_conditions_this_only,
                )
            );
        }

        //now set the transaction object if possible
        $this->txn = $this->reg_obj instanceof EE_Registration
            ? $this->reg_obj->transaction()
            : $this->_maybe_get_transaction();

        $this->_assemble_data();
    }


    /**
     * If the incoming registrations all share the same transaction then this will return the transaction object shared
     * among the registrations. Otherwise the transaction object is set to null because its intended to only represent
     * one transaction.
     *
     * @return EE_Transaction|null
     * @throws EE_Error
     * @throws EntityNotFoundException
     */
    protected function _maybe_get_transaction()
    {
        $first_transaction = null;
        foreach ($this->reg_objs as $registration) {
            $transaction = $registration->transaction();
            if ($transaction instanceof EE_Transaction) {
                //do they match
                if ($first_transaction !== $transaction) {
                    //has $first_transaction been set yet?
                    if ($first_transaction === null) {
                        $first_transaction = $transaction;
                        continue;
                    }
                    return null;
                }
            }
        }
        return $first_transaction;
    }
}