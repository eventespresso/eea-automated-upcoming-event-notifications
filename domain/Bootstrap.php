<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain;

use EE_Dependency_Map;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\RegisterCustomShortcodeLibrary;
use EventEspresso\core\domain\DomainInterface;
use EventEspresso\core\domain\values\FilePath;
use EventEspresso\core\domain\values\Version;
use EventEspresso\core\services\loaders\Loader;
use ReflectionException;

class Bootstrap
{


    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        if (defined('EVENT_ESPRESSO_VERSION')
            && class_exists('EE_Addon')
            && class_exists('EventEspresso\core\domain\DomainBase')
            && version_compare(EVENT_ESPRESSO_VERSION, '4.9.81.p', '>')
        ) {
            add_action('AHEE__EE_System__load_espresso_addons', [$this, 'loadAutomatedUpcomingEventNotifications']);
        } else {
            add_action('admin_notices', [$this, 'displayActivationErrors']);
        }
        add_action('activated_plugin', [$this, 'printPluginActivationErrors']);
    }


    /**
     * preps, loads, and registers AutomatedUpcomingEventNotifications
     *
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function loadAutomatedUpcomingEventNotifications()
    {
        $this->registerDependencies();
        // initialize add-on
        AutomatedUpcomingEventNotifications::registerAddon($this->getAuenDomain());
    }


    private function registerDependencies()
    {
        EE_Dependency_Map::instance()->add_alias(
            Domain::class,
            DomainInterface::class,
            AutomatedUpcomingEventNotifications::class
        );
        EE_Dependency_Map::instance()->add_alias(
            Domain::class,
            DomainInterface::class,
            RegisterCustomShortcodeLibrary::class
        );
        $loader = function () {
            return Bootstrap::getAuenDomain();
        };
        EE_Dependency_Map::register_class_loader(Domain::class, $loader);
        EE_Dependency_Map::register_dependencies(
            AutomatedUpcomingEventNotifications::class,
            [
                EE_Dependency_Map::class => EE_Dependency_Map::load_from_cache,
                Domain::class            => $loader,
                Loader::class            => EE_Dependency_Map::load_from_cache,
            ],
            EE_Dependency_Map::OVERWRITE_DEPENDENCIES
        );
    }


    /**
     * @returns Domain
     */
    public static function getAuenDomain()
    {
        static $domain;
        if (! $domain instanceof Domain) {
            $domain = new Domain(
                new FilePath(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_FILE),
                Version::fromString(EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_VERSION)
            );
        }
        return $domain;
    }


    /**
     * displays an activation error admin notice
     */
    public function displayActivationErrors()
    {
        unset($_GET['activate'], $_REQUEST['activate']);
        if (! function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        deactivate_plugins(plugin_basename(__FILE__));
        ?>
        <div class="error">
            <p>
                <?php
                printf(
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
     * Capture any unexpected activation errors for debugging
     */
    public function printPluginActivationErrors()
    {
        if (WP_DEBUG && defined('EVENT_ESPRESSO_UPLOAD_DIR')) {
            $activation_errors = ob_get_contents();
            file_put_contents(
                EVENT_ESPRESSO_UPLOAD_DIR
                . 'logs/'
                . 'espresso_automated_upcoming_event_notification_plugin_activation_errors.html',
                $activation_errors
            );
        }
    }
}