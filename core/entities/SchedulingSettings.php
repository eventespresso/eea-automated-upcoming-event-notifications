<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\core\entities;

use EE_Error;
use EE_Message_Template_Group;

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
     * Represents the string used to reference the extra meta for holding the days before threshold.
     *
     * @var string
     */
    const DAYS_BEFORE_THRESHOLD = 'automation_days_before';


    /**
     * Represents the string used to reference the extra meta for holding whether the automation is active or not.
     */
    const AUTOMATION_ACTIVE = 'automation_active';


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
        if (! isset($this->cache[SchedulingSettings::DAYS_BEFORE_THRESHOLD])) {
            $this->cache[SchedulingSettings::DAYS_BEFORE_THRESHOLD] = (int)$this->message_template_group->get_extra_meta(
                SchedulingSettings::DAYS_BEFORE_THRESHOLD,
                true,
                5
            );
        }
        return $this->cache[SchedulingSettings::DAYS_BEFORE_THRESHOLD];
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
            SchedulingSettings::DAYS_BEFORE_THRESHOLD,
            (int)$new_threshold
        );
        if ($saved) {
            $this->cache[SchedulingSettings::DAYS_BEFORE_THRESHOLD] = (int)$new_threshold;
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
        if (! isset($this->cache[SchedulingSettings::AUTOMATION_ACTIVE])) {
            $this->cache[SchedulingSettings::AUTOMATION_ACTIVE] = (bool)$this->message_template_group->get_extra_meta(
                SchedulingSettings::AUTOMATION_ACTIVE,
                true,
                false
            );
        }
        return $this->cache[SchedulingSettings::AUTOMATION_ACTIVE];
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
        $saved = $this->message_template_group->update_extra_meta(
            SchedulingSettings::AUTOMATION_ACTIVE,
            $is_active
        );
        if ($saved) {
            $this->cache[SchedulingSettings::AUTOMATION_ACTIVE] = $is_active;
        }
        return $saved;
    }
}