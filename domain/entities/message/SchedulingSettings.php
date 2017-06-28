<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message;

use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');


/**
 * Provides access to the scheduling settings attached to a specific message template group.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \core\entities
 * @author     Darren Ethier
 * @since      1.0.0
 */
class SchedulingSettings
{
    /**
     * Internal cache for the settings.
     *
     * @var array
     */
    private $cache = array();


    /**
     * @var  EE_Message_Template_Group
     */
    private $message_template_group;


    /**
     * SchedulingSettings constructor.
     *
     * @param EE_Message_Template_Group $message_template_group
     */
    public function __construct(EE_Message_Template_Group $message_template_group)
    {
        $this->message_template_group = $message_template_group;
    }


    /**
     * Returns whatever is set for the days before threshold for this schedule.
     *
     * @return int
     * @throws EE_Error
     */
    public function currentThreshold()
    {
        if (! isset($this->cache[Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER])) {
            $this->cache[Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER] = (int)$this->message_template_group->get_extra_meta(
                Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER,
                true,
                1
            );
        }
        return $this->cache[Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER];
    }


    /**
     * Sets the days before threshold to the provided value.
     *
     * @param int $new_threshold
     * @return bool|int @see EE_Base_Class::update_extra_meta
     * @throws EE_Error
     */
    public function setCurrentThreshold($new_threshold)
    {
        $saved = $this->message_template_group->update_extra_meta(
            Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER,
            (int)$new_threshold
        );
        if ($saved) {
            $this->cache[Constants::DAYS_BEFORE_THRESHOLD_IDENTIFIER] = (int)$new_threshold;
        }
        return $saved;
    }


    /**
     * Returns whether the automation is active or not.
     *
     * @return bool
     * @throws EE_Error
     */
    public function isActive()
    {
        if (! isset($this->cache[Constants::AUTOMATION_ACTIVE_IDENTIFIER])) {
            $this->cache[Constants::AUTOMATION_ACTIVE_IDENTIFIER] = (bool)$this->message_template_group->get_extra_meta(
                Constants::AUTOMATION_ACTIVE_IDENTIFIER,
                true,
                false
            );
        }
        return $this->cache[Constants::AUTOMATION_ACTIVE_IDENTIFIER];
    }


    /**
     * Used to set the automation to be active or not.
     *
     * @param bool $is_active
     * @return bool|int @see EE_Base_Class::update_extra_meta
     * @throws EE_Error
     */
    public function setIsActive($is_active)
    {
        $is_active = filter_var($is_active, FILTER_VALIDATE_BOOLEAN);
        $saved     = $this->message_template_group->update_extra_meta(
            Constants::AUTOMATION_ACTIVE_IDENTIFIER,
            $is_active
        );
        if ($saved) {
            $this->cache[Constants::AUTOMATION_ACTIVE_IDENTIFIER] = $is_active;
        }
        return $saved;
    }
}