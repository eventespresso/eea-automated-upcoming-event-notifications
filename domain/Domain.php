<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain;

use EventEspresso\core\domain\DomainBase;

/**
 * Domain
 * A container for all constants used in this domain.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
class Domain extends DomainBase
{
    /**
     * EE Core Version Required for Add-on
     */
    const CORE_VERSION_REQUIRED = '4.9.81.p';


    /**
     * WordPress Version Required for Add-on
     */
    const WP_VERSION_REQUIRED = '5.2';


    /**
     * String used as the prefix for the registration tracker meta key for the RegistrationsNotifiedCommand
     */
    const META_KEY_PREFIX_REGISTRATION_TRACKER = 'ee_auen_processed_';


    /**
     * String used as the prefix for the admin tracker meta key for the RegistrationsNotifiedCommand.
     */
    const META_KEY_PREFIX_ADMIN_TRACKER = 'ee_auen_processed_for_admin';

    /**
     * Represents the string used to reference the extra meta for holding the days before threshold.
     *
     * @var string
     */
    const META_KEY_DAYS_BEFORE_THRESHOLD = 'automation_days_before';


    /**
     * Slug for the Automate Upcoming Event message type
     */
    const MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT = 'automate_upcoming_event';


    /**
     * Slug for the Automate Upcoming Datetime message type
     */
    const MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME = 'automate_upcoming_datetime';
}
