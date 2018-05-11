<?php

use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;

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
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    public function __construct($data = array())
    {
        if (! self::validate_incoming_data($data)) {
            throw new InvalidArgumentException(
                sprintf(
                    esc_html__(
                        'The incoming data argument for %1$s is expected to be an array where the first element is an %2$s object or %3$s and the second element is either an %4$s object or an array of %4$s objects.',
                        'event_espresso'
                    ),
                    __METHOD__,
                    'EE_Datetime',
                    'null',
                    'EE_Registration'
                )
            );
        }
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
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public static function convert_data_for_persistent_storage($datetime_and_registrations)
    {
        // make sure $registrations are an array.
        /** @noinspection ArrayCastingEquivalentInspection */
        $datetime_and_registrations[1] = is_array($datetime_and_registrations[1])
            ? $datetime_and_registrations[1]
            : array($datetime_and_registrations[1]);

        if (! self::validate_incoming_data($datetime_and_registrations)) {
            return array();
        }

        $datetime_id = $datetime_and_registrations[0] instanceof EE_Datetime
            ? $datetime_and_registrations[0]->ID()
            : $datetime_and_registrations[0];
        $first_registration_item = reset($datetime_and_registrations[1]);
        if ($first_registration_item instanceof EE_Registration) {
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
        } else {
            $registration_ids = $datetime_and_registrations[1];
        }


        return array($datetime_id, $registration_ids);
    }


    /**
     * Validate incoming data to make sure its the expected format.  We could have either ids or the actual objects, so
     * let's simply validate
     *
     * @param $datetime_and_registrations
     * @return bool
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    protected static function validate_incoming_data(array $datetime_and_registrations)
    {
        // first split the array into its component parts for validation
        /**
         * We allow null for the first argument.  But if its not null, then it must be either a `EE_Datetime` object or
         * an integer ($datetime_id).
         */
        if ($datetime_and_registrations[0] !== null
            && ! $datetime_and_registrations[0] instanceof EE_Datetime
            && ! is_int($datetime_and_registrations[0])
        ) {
            return false;
        }

        // next is the registrations.
        $first_item = reset($datetime_and_registrations[1]);

        if ($first_item instanceof EE_Registration) {
            return true;
        }

        // okay we've made it here so we likely have a set of registration ids.  In which case let's do some simple
        // validation that at least ensures these reg_ids exist for registrations in the db.
        if (is_int($first_item)) {
            $count_of_actual_registrations = EEM_Registration::instance()->count(
                array(
                    array(
                        'REG_ID' => array('IN', $datetime_and_registrations[1])
                    )
                )
            );
            return $count_of_actual_registrations === count($datetime_and_registrations[1]);
        }

        // made it here, then it ain't valid.
        return false;
    }


    /**
     * Receives an array that was prepared by convert_data_for_persistent_storage for the data and converts it back to
     * the formats expected when instantiating this class.
     *
     * @param array $data
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
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
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function _setup_data()
    {
        $data                    = $this->data();
        $this->specific_datetime = $data[0];

        // assign registrations
        $this->reg_objs = is_array($data[1]) ? $data[1] : array();

        // if the incoming registration value is not in an array format then set that as the reg_obj.  Then we get the
        // matching registrations linked to the same datetime as this registration and assign that to the reg_objs
        // property.
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

        // now set the transaction object if possible
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
            if ($transaction instanceof EE_Transaction && $first_transaction !== $transaction) {
                // has $first_transaction been set yet?
                if ($first_transaction === null) {
                    $first_transaction = $transaction;
                    continue;
                }
                return null;
            }
        }
        return $first_transaction;
    }
}
