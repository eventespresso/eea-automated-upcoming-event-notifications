<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\core\services\commands\CommandInterface;
use EE_Registration;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class RegistrationsNotifiedCommandHandler extends CommandHandler
{
    /**
     * @param RegistrationsNotifiedCommand $command
     * @return mixed
     */
    public function handle(CommandInterface $command)
    {
        $this->setRegistrationsProcessed($command->getRegistrations(), $command->getIdentifier());
    }


    /**
     * Receives an array of registrations and calls `setRegistrationReceivedNotification` for each registration.
     * If you need the response from the setting of this value (success/fail) then its suggested you call
     * `setRegistrationReceivedNotification`
     *
     * @param EE_Registration[] $registrations
     * @param string            $id_ref
     * @throws EE_Error
     */
    protected function setRegistrationsProcessed(array $registrations, $id_ref)
    {
        if ($registrations) {
            array_walk(
                $registrations,
                function ($registration) use ($id_ref) {
                    if (! $registration instanceof EE_Registration) {
                        return;
                    }
                    $this->setRegistrationProcessed($registration, $id_ref);
                }
            );
        }
    }


    /**
     * Use to save the flag indicating the registration has received a notification from being triggered.
     *
     * @param EE_Registration $registration
     * @param string          $id_ref
     * @return int|bool        @see EE_Base_Class::add_extra_meta
     * @throws EE_Error
     */
    protected function setRegistrationProcessed(EE_Registration $registration, $id_ref)
    {
        return $registration->update_extra_meta(
            Constants::REGISTRATION_TRACKER_PREFIX . $id_ref,
            true
        );
    }

}