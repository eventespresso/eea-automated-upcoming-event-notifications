<?php

use EventEspresso\AutomatedUpcomingEventNotifications\domain\AutomatedUpcomingEventNotifications;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\domain\DomainFactory;
use EventEspresso\core\domain\DomainInterface;
use EventEspresso\core\domain\values\FilePath;
use EventEspresso\core\domain\values\FullyQualifiedName;
use EventEspresso\core\domain\values\Version;
use EventEspresso\core\services\loaders\Loader;
use EventEspresso\core\services\loaders\LoaderFactory;


add_action(
    'AHEE__EE_System__load_espresso_addons',
    function () {
        if (defined('EVENT_ESPRESSO_VERSION')
            && class_exists('EE_Addon')
            && class_exists('EventEspresso\core\domain\DomainBase')
            && version_compare(EVENT_ESPRESSO_VERSION, '4.9.81.p', '>')
        ) {
            // register namespace
            EE_Psr4AutoloaderInit::psr4_loader()->addNamespace(
                'EventEspresso\AutomatedUpcomingEventNotifications',
                __DIR__
            );
            EE_Dependency_Map::instance()->add_alias(
                Domain::class,
                DomainInterface::class,
                AutomatedUpcomingEventNotifications::class
            );
            // register dependencies
            EE_Dependency_Map::register_dependencies(
                AutomatedUpcomingEventNotifications::class,
                array(
                    EE_Dependency_Map::class => EE_Dependency_Map::load_from_cache,
                    Domain::class => EE_Dependency_Map::load_from_cache,
                    Loader::class => EE_Dependency_Map::load_from_cache
                )
            );
            /** @var Domain $domain */
            $domain = DomainFactory::getShared(
                new FullyQualifiedName(Domain::class),
                [
                    new FilePath(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_FILE),
                    Version::fromString(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_VERSION),
                ]
            );
            LoaderFactory::getLoader()->share(Domain::class, $domain);
            // initialize add-on
            AutomatedUpcomingEventNotifications::registerAddon($domain);
        } else {
            add_action('admin_notices', 'espresso_automated_upcoming_event_notification_activation_error');
        }
    }
);

/**
 * This is a simple verification that EE core is active.  If its not, then we need to deactivate and show a notice.
 */
add_action(
    'init',
    function () {
        if (! did_action('AHEE__EE_System__load_espresso_addons')) {
            add_action('admin_notices', 'espresso_automated_upcoming_event_notification_activation_error');
        }
    },
    1
);

/**
 *    displays activation error admin notice
 */
function espresso_automated_upcoming_event_notification_activation_error()
{
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
                    'Event Espresso Automated Upcoming Event Notifications add-on could not be activated. Please ensure that Event Espresso version %1$s or higher is running',
                    'event_espresso'
                ),
                '4.9.81.p'
            ); ?>
        </p>
    </div>
    <?php
}

/**
 * Capture any activation errors for debugging
 */
add_action(
    'activated_plugin',
    function ($plugin) {
        $auen_basename = plugin_basename(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_FILE);
        if ($plugin === $auen_basename && WP_DEBUG && defined('EVENT_ESPRESSO_UPLOAD_DIR')) {
            $activation_errors = ob_get_contents();
            file_put_contents(
                EVENT_ESPRESSO_UPLOAD_DIR
                . 'logs/'
                . 'espresso_automated_upcoming_event_notification_plugin_activation_errors.html',
                $activation_errors
            );
        }
    }
);
