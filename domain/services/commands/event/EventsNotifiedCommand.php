<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event;

use EventEspresso\core\services\commands\Command;
use EE_Event;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * EventsNotifiedCommand
 * Command for tracking events that have been notified.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EventsNotifiedCommand extends Command
{
    /**
     * @var EE_Event[]
     */
    private $events;


    /**
     * This will be the message type context for which these events received notifications.
     * @var string
     */
    private $context;


    /**
     * EventsNotifiedCommand constructor.
     *
     * @param        array EE_Event[] $events
     * @param        $context
     */
    public function __construct(array $events, $context)
    {
        $this->events = $this->validateEvents($events);
        $this->context = $context;
    }


    /**
     * Ensures the given array only contains `EE_Event` objects and filters out non
     * EE_Event objects from the array.
     *
     * @param EE_Event[] $events
     * @return EE_Event[]
     */
    private function validateEvents(array $events)
    {
        return array_filter(
            $events,
            function ($event) {
                return $event instanceof EE_Event;
            }
        );
    }


    /**
     * @return EE_Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }
}
