<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\factory;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed');

use LogicException;

/**
 * A very simple registry for simple no argument classes.
 * This is used for any classes we want to make sure there is only ever one instance of in play without using singleton
 * pattern.
 *
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage core\factory
 * @author     Darren Ethier
 * @since      1.0.0
 */
class Registry
{
    private $registry;

    public function get($fqcn)
    {
        if (! isset($this->registry[$fqcn])
        ) {
            if (! class_exists($fqcn)) {
                throw new LogicException(
                    sprintf(
                        esc_html__(
                            'The %1$s class does not exist.  Make sure you are passing in the fully qualified class name.',
                            'event_espresso'
                        ),
                        $fqcn
                    )
                );
            }
            $this->registry[$fqcn] = new $fqcn;
        }
        return $this->registry[$fqcn];
    }


    public function call($fqcn)
    {
        $this->get($fqcn);
    }
}