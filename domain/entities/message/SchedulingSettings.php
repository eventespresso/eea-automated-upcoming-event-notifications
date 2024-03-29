<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message;

use EE_Error;
use EE_Message_Template_Group;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidIdentifierException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;

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
     * @param string $context
     * @return int
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function currentThreshold($context)
    {
        $meta_key = $this->getMessageTypeMetaKey($context);
        if (! isset($this->cache[ $meta_key ])) {
            $this->cache[ $meta_key ] = (int) $this->message_template_group->get_extra_meta(
                $meta_key,
                true,
                1
            );
        }
        return $this->cache[ $meta_key ];
    }


    /**
     * Sets the days before threshold to the provided value for the given context.
     *
     * @param int    $new_threshold
     * @param string $context
     * @return bool|int @see EE_Base_Class::update_extra_meta
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function setCurrentThreshold($new_threshold, $context)
    {
        $meta_key = $this->getMessageTypeMetaKey($context);
        $saved = $this->message_template_group->update_extra_meta(
            $meta_key,
            (int) $new_threshold
        );
        if ($saved) {
            $this->cache[ $meta_key ] = (int) $new_threshold;
        }
        return $saved;
    }


    /**
     * @return array
     * @throws EE_Error
     * @throws InvalidIdentifierException
     */
    public function allActiveContexts()
    {
        $cache_key = EE_Message_Template_Group::ACTIVE_CONTEXT_RECORD_META_KEY_PREFIX
                     . '_'
                     . $this->message_template_group->message_type();
        if (! isset($this->cache[ $cache_key ])) {
            $contexts = array_keys($this->message_template_group->contexts_config());
            $this->cache[ $cache_key ] = array();
            foreach ($contexts as $context) {
                if ($this->message_template_group->is_context_active($context)) {
                    $this->cache[ $cache_key ][] = $context;
                }
            }
        }
        return $this->cache[ $cache_key ];
    }


    /**
     * @return EE_Message_Template_Group
     */
    public function getMessageTemplateGroup()
    {
        return $this->message_template_group;
    }

    /**
     * @return string|null
     * @param string $context
     */
    public function getMessageTypeMetaKey($context)
    {
        $meta_key = null;
        $message_type = $this->message_template_group->message_type();
        switch ($message_type) {
            // 'Post' message types use DAYS_AFTER threshold
            case Domain::MESSAGE_TYPE_AUTOMATE_POST_EVENT:
            case Domain::MESSAGE_TYPE_AUTOMATE_POST_DATETIME:
                $meta_key = Domain::META_KEY_DAYS_AFTER_THRESHOLD . '_' . $context;
                break;
            // 'Upcoming' message types use DAYS_BEFORE threshold
            case Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT:
            case Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME:
            default: // <== this line is actually not necessary
                $meta_key = Domain::META_KEY_DAYS_BEFORE_THRESHOLD . '_' . $context;
                break;
        }
        return $meta_key;
    }
}
