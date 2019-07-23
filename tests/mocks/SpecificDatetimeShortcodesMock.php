<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EE_Error;
use EE_Specific_Datetime_Shortcodes;
use EE_Automate_Upcoming_Datetime_message_type;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;

require_once(EE_AUTOMATED_UPCOMING_EVENT_PLUGIN_DIR . 'domain/services/messages/EE_Specific_Datetime_Shortcodes.lib.php');
/** @noinspection LongInheritanceChainInspection */

/**
 * SpecificDatetimeShortcodesMock
 *
 *
 * @package EventEspresso\AutomateUpcomingEventNotificationsTests\mocks
 * @author  Darren Ethier
 * @since   1.0.0
 */
class SpecificDatetimeShortcodesMock extends EE_Specific_Datetime_Shortcodes
{
    /**
     * Sets up things for testing the `parser` method of the EE_Specific_Datetime_Shortcodes library.
     *
     * @param       $shortcode
     * @param array $datetime_and_registrations  Expect an array with the first element a Datetime and the second an
     *                                           array of registrations.
     * @return string
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function parserMockWithData($shortcode, array $datetime_and_registrations)
    {
        //setup some dummy data that the actual parser is looking for.  EE core has test coverage on the actual parser
        //so there's no need to duplicate that in our tests.  Here the only thing needing tested is that given the correct
        //data, the shortcodes will parse as expected.
        $this->_message_type = new EE_Automate_Upcoming_Datetime_message_type();
        $data_handler = $this->_message_type->get_data_handler($datetime_and_registrations);
        $data_handler = 'EE_Messages_' . $data_handler . '_incoming_data';
        $data_handler = new $data_handler($datetime_and_registrations);
        $this->_message_type->get_addressees($data_handler, 'attendee');

        //k there should be enough set on this object now for the parser to correctly work.
        return $this->_parser($shortcode);
    }


    /**
     * This is for testing expectations when there is not any data available to parse the shortcode correctly with.
     * It should return an empty string and NOT fatal.
     *
     * @param $shortcode
     * @return string
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function parserMockWithoutData($shortcode)
    {
        $this->_message_type = null;
        return $this->_parser($shortcode);
    }
}