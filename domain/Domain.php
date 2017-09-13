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
    const CORE_VERSION_REQUIRED = '4.9.44.rc.022';


    /**
     * String used as the prefix for the registration tracker meta key for the RegistrationsNotifiedCommand
     */
    const META_KEY_PREFIX_REGISTRATION_TRACKER = 'ee_auen_processed_';

    /**
     * Represents the string used to reference the extra meta for holding the days before threshold.
     *
     * @var string
     */
    const META_KEY_DAYS_BEFORE_THRESHOLD = 'automation_days_before';


    /**
     * Represents the string used to reference the extra meta for holding whether the automation is active or not.
     */
    const META_KEY_AUTOMATION_ACTIVE = 'automation_active';
}
