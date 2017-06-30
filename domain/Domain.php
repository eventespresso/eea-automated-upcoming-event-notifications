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
    const CORE_VERSION_REQUIRED = '4.9.39.rc.006';


    /**
     * String used as the prefix for the registration tracker meta key for the RegistrationsNotifiedCommand
     */
    const REGISTRATION_TRACKER_PREFIX = 'ee_auen_processed_';

    /**
     * Represents the string used to reference the extra meta for holding the days before threshold.
     *
     * @var string
     */
    const DAYS_BEFORE_THRESHOLD_IDENTIFIER = 'automation_days_before';


    /**
     * Represents the string used to reference the extra meta for holding whether the automation is active or not.
     */
    const AUTOMATION_ACTIVE_IDENTIFIER = 'automation_active';
}
