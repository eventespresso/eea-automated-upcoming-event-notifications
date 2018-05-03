<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message;

use EE_Message_Template_Group;
use EventEspresso\core\services\commands\Command;

/**
 * UpcomingNotificationsCommand
 * Abstract parent for all upcoming notifications commands.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\message
 * @subpackage
 * @author  Darren Ethier
 * @since   1.0.0
 */
abstract class UpcomingNotificationsCommand extends Command
{
    /**
     * @var EE_Message_Template_Group[]
     */
    private $message_template_groups;


    /**
     * UpcomingNotificationsCommand constructor.
     *
     * @param array $message_template_groups
     */
    public function __construct(array $message_template_groups)
    {
        $this->message_template_groups = $this->validateGroups($message_template_groups);
    }


    /**
     * Simply filters to make sure the array is only an array of EE_Message_Template_Group for the correct message_type
     * in this command.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @return EE_Message_Template_Group[]
     */
    private function validateGroups(array $message_template_groups)
    {
        // make sure we only have instances of EE_Message_Template_Group in this array.
        // also make sure the instance is only for a message type this is command is being implemented for.
        return array_filter(
            $message_template_groups,
            function ($message_template_group) {
                return $message_template_group instanceof EE_Message_Template_Group
                       && $message_template_group->message_type() === $this->getMessageTypeNotificationIsFor();
            }
        );
    }


    /**
     * Returns the message type slug this notification command is for.
     *
     * @return string
     */
    abstract protected function getMessageTypeNotificationIsFor();


    /**
     * @return \EE_Message_Template_Group[]
     */
    public function getMessageTemplateGroups()
    {
        return $this->message_template_groups;
    }
}
