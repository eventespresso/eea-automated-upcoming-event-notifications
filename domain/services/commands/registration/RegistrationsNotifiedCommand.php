<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\registration;

use EventEspresso\core\services\commands\Command;
use EE_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * RegistrationsNotifiedCommand
 * Command for tracking registrations that have been notified.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\registration
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
     * This will be the message type context for which these registrations got notified on.
     * @var string
     */
    private $context;


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
     * @param        $context
     * @param string $id_ref
     */
    public function __construct(array $registrations, $context, $id_ref)
    {
        $this->registrations = $this->validateRegistrations($registrations);
        $this->identifier    = $id_ref;
        $this->context = $context;
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


    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }
}
