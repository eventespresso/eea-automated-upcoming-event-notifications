<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event;

use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\core\services\commands\CommandInterface;
use EE_Event;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * EventsNotifiedCommandHandler
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EventsNotifiedCommandHandler extends CommandHandler
{
    /**
     * @param EventsNotifiedCommand|CommandInterface $command
     * @return int  count of events processed.
     * @throws EE_Error
     */
    public function handle(CommandInterface $command)
    {
        return $this->setEventsProcessed(
            $command->getEvents(),
            $command->getContext()
        );
    }


    /**
     * Receives an array of events and calls `setEventProcessed` for each event.
     * If you need the response from the setting of this value (success/fail) then its suggested you call
     * `setEventProcessed`
     *
     * @param EE_Event[] $events
     * @param string            $context  Represents the message type context for which these events are being
     *                                    processed for.
     * @return int count of events successfully processed.
     * @throws EE_Error
     */
    protected function setEventsProcessed(array $events, $context)
    {
        $count = 0;
        if ($events) {
            foreach ($events as $event) {
                $increment_count = false;
                if (! $event instanceof EE_Event) {
                    continue;
                }
                $this->setEventProcessed($event, $context);
                if ($increment_count) {
                    $count++;
                }
            }
        }
        return $count;
    }


    /**
     * Use to save the flag indicating the event has received a notification.
     *
     * @param EE_Event $event
     * @param string      $context Will determine what meta key to use.
     * @return int|bool        @see EE_Base_Class::add_extra_meta
     * @throws EE_Error
     */
    protected function setEventProcessed(EE_Event $event, $context)
    {
        $meta_key_prefix = $context === 'admin'
            ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
            : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
        return $event->update_extra_meta(
            $meta_key_prefix,
            true
        );
    }
}
