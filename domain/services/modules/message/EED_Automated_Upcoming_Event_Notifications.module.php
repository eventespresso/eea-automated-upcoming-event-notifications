<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\Controller;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\LoaderFactory;

/**
 * EED_Automated_Upcoming_Event_Notifications
 * Main module for add-on.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications
 * @author  Darren Ethier
 * @since   1.0.0
 */
class EED_Automated_Upcoming_Event_Notifications extends EED_Module
{

    /**
     * code that runs at 'init' 9 within the admin (includes ajax) context
     *
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public static function set_hooks_admin()
    {
        LoaderFactory::getLoader()->getShared(Controller::class);
    }

    /**
     * Not used in this module
     *
     * @access    public
     * @param       WP $WP
     */
    public function run($WP)
    {
        // noop
    }
}
