<?php
/*
  Plugin Name: Event Espresso - Automated Upcoming Event Notification (EE 4.9.44+)
  Plugin URI: http://www.eventespresso.com
  Description: Adds new message types to the EE messages system to help with automating messages to attendees of upcoming Events and Datetimes.
  Version: 1.0.5.p
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version	 	EE4
 */

// define versions and this file
define('EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_VERSION', '1.0.5.p');
define('EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_FILE', __FILE__);

// check php version: requires PHP 5.6 ++
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50600) {
    require_once __DIR__ . '/bootstrap.php';
} else {
    // if not sufficient then deactivate and show notice
    add_action(
        'admin_notices',
        function () {
            unset($_GET['activate'], $_REQUEST['activate']);
            if (! function_exists('deactivate_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            deactivate_plugins(plugin_basename(__FILE__));
            ?>
            <div class="error">
                <p>
                    <?php printf(
                        esc_html__(
                            'Event Espresso Automated Upcoming Event Notifications add-on could not be activated because it requires PHP version %s or greater.',
                            'event_espresso'
                        ),
                        '5.6.0'
                    ); ?>
                </p>
            </div>
            <?php
        }
    );
}

