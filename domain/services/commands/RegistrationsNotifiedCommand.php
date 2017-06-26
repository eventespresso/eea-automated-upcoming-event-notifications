<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;
use EventEspresso\core\services\commands\Command;
use EE_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * RegistrationsNotifiedCommand
 * Command for tracking registrations that have been notified.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands
 * @author  Darren Ethier
 * @since   1.0.0
 */
class RegistrationsNotifiedCommand extends Command
{
    /**
     * @var EE_Registration[]
     */
    private $registrations;


    /**
     * A unique string used as the identifier for tracking this registration is notified.
     *
     * @var
     */
    private $identifier;


    /**
     * RegistrationsNotifiedCommand constructor.
     *
     * @param        array EE_Registration[] $registrations
     * @param string $id_ref
     */
    public function __construct(array $registrations, $id_ref)
    {
        $this->registrations = $this->validateRegistrations($registrations);
        $this->identifier    = $id_ref;
    }


    /**
     * Ensures the given array only contains `EE_Registration` objects and filters out non
     * EE_Registration objects from the array.
     *
     * @param EE_Registration[] $registrations
     * @return EE_Registration[]
     */
    private function validateRegistrations(array $registrations)
    {
        return array_filter(
            $registrations,
            function ($registration) {
                return $registration instanceof EE_Registration;
            }
        );
    }


    /**
     * @return EE_Registration[]
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }


    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
