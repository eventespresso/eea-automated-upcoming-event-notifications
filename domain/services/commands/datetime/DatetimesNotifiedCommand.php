<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\datetime;

use EventEspresso\core\services\commands\Command;
use EE_Datetime;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * DatetimesNotifiedCommand
 * Command for tracking datetimes that have been notified.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\registration
 * @author  Darren Ethier
 * @since   1.0.0
 */
class DatetimesNotifiedCommand extends Command
{
    /**
     * @var EE_Datetime[]
     */
    private $datetimes;


    /**
     * This will be the message type context for which these datetimes received notifications.
     * @var string
     */
    private $context;


    /**
     * DatetimesNotifiedCommand constructor.
     *
     * @param        array EE_Datetime[] $datetimes
     * @param        $context
     */
    public function __construct(array $datetimes, $context)
    {
        $this->datetimes = $this->validateDatetimes($datetimes);
        $this->context = $context;
    }


    /**
     * Ensures the given array only contains `EE_Datetime` objects and filters out non
     * EE_Datetime objects from the array.
     *
     * @param EE_Datetime[] $datetimes
     * @return EE_Datetime[]
     */
    private function validateDatetimes(array $datetimes)
    {
        return array_filter(
            $datetimes,
            function ($datetime) {
                return $datetime instanceof EE_Datetime;
            }
        );
    }


    /**
     * @return EE_Datetime[]
     */
    public function getDatetimes()
    {
        return $this->datetimes;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }
}
