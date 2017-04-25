<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class EED_Automated_Upcoming_Event_Notifications extends EED_Module
{

    /**
     * code that runs at 'init' 9 within the admin (includes ajax) context
     */
    public static function set_hooks_admin()
    {
        EE_Automated_Upcoming_Event_Notification::loader()->load(
            'EventEspresso\AutomatedUpcomingEventNotifications\core\messages\admin\Controller'
        );
    }

    /**
     * Not used in this module
     *
     * @access    public
     * @param       WP $WP
     */
    public function run($WP)
    {
    }
}
