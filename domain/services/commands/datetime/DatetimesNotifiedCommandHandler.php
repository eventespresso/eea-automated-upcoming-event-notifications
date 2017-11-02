<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\datetime;

use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\core\services\commands\CommandInterface;
use EE_Datetime;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * DatetimesNotifiedCommandHandler
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\datetime
 * @author  Darren Ethier
 * @since   1.0.0
 */
class DatetimesNotifiedCommandHandler extends CommandHandler
{
    /**
     * @param DatetimesNotifiedCommand|CommandInterface $command
     * @return int  count of datetimes processed.
     * @throws EE_Error
     */
    public function handle(CommandInterface $command)
    {
        return $this->setDatetimesProcessed(
            $command->getDatetimes(),
            $command->getContext()
        );
    }


    /**
     * Receives an array of datetimes and calls `setDatetimeProcessed` for each datetime.
     * If you need the response from the setting of this value (success/fail) then its suggested you call
     * `setDatetimeProcessed`
     *
     * @param EE_Datetime[] $datetimes
     * @param string            $context  Represents the message type context for which these datetimes are being
     *                                    processed for.
     * @return int count of datetimes successfully processed.
     * @throws EE_Error
     */
    protected function setDatetimesProcessed(array $datetimes, $context)
    {
        $count = 0;
        if ($datetimes) {
            foreach ($datetimes as $datetime) {
                $increment_count = false;
                if (! $datetime instanceof EE_Datetime) {
                    continue;
                }
                $this->setDatetimeProcessed($datetime, $context);
                if ($increment_count) {
                    $count++;
                }
            }
        }
        return $count;
    }


    /**
     * Use to save the flag indicating the datetime has received a notification.
     *
     * @param EE_Datetime $datetime
     * @param string      $context Will determine what meta key to use.
     * @return int|bool        @see EE_Base_Class::add_extra_meta
     * @throws EE_Error
     */
    protected function setDatetimeProcessed(EE_Datetime $datetime, $context)
    {
        $meta_key_prefix = $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
        return $datetime->update_extra_meta(
            $meta_key_prefix,
            true
        );
    }
}
