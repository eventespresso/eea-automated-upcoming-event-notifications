<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EventEspresso\core\services\commands\CommandInterface;
use EEM_Registration;
use EED_Automated_Upcoming_Event_Notification_Messages;
use EE_Registration;
use EE_Message_Template_Group;
use EEM_Message_Template_Group;
use EE_Error;
use EventEspresso\core\services\commands\CompositeCommandHandler;
use EE_Registry;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

/**
 * UpcomingNotificationsCommandHandler
 * Abstract class for all notifications command handlers.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands
 * @author  Darren Ethier
 * @since   1.0.0
 */
abstract class UpcomingNotificationsCommandHandler extends CompositeCommandHandler
{

    /**
     * @var EEM_Registration
     */
    protected $registration_model;


    /**
     * @var EE_Registry
     */
    protected $registry;


    /**
     * UpcomingNotificationsCommandHandler constructor.
     *
     * @param EEM_Registration $registration_model
     * @param EE_Registry      $registry
     */
    public function __construct(EEM_Registration $registration_model, EE_Registry $registry)
    {
        $this->registration_model = $registration_model;
        $this->registry           = $registry;
    }


    /**
     * @param UpcomingNotificationsCommand|CommandInterface $command
     * @throws EE_Error
     */
    public function handle(CommandInterface $command)
    {
        $data = $this->getData($command->getMessageTemplateGroups());
        $this->process($data);
    }


    /**
     * This should handle setting up the data that would be sent into the process method.
     * The expectation is that all registrations in an event that belong to the trigger threshold for ANY datetime in
     * the event are returned.
     *
     * @param EEM_Message_Template_Group[] $message_template_groups
     * @return array
     * @throws EE_Error
     */
    protected function getData(array $message_template_groups)
    {
        $data = array();
        if (! empty($message_template_groups)) {
            $data = $this->getDataForCustomMessageTemplateGroups($message_template_groups);
            $data = $this->getDataForGlobalMessageTemplateGroup(
                $message_template_groups,
                $data
            );
        }
        return $data;
    }


    /**
     * This takes care of triggering the actual messages
     *
     * @param array  $data
     * @param string $message_type
     */
    protected function triggerMessages(array $data, $message_type)
    {
        /**
         * This filter allows client code to handle the actual sending of messages differently if desired
         */
        if (apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_Domain_Services_Commands_UpcomingNotificationsCommandHandler__triggerMessages__do_default_trigger',
            true,
            $data,
            $message_type
        )) {
            EED_Automated_Upcoming_Event_Notification_Messages::prep_and_queue_messages(
                $message_type,
                $data
            );
        }
    }


    /**
     * Receives an array of registrations and calls `setRegistrationReceivedNotification` for each registration.
     * If you need the response from the setting of this value (success/fail) then its suggested you call
     * `setRegistrationReceivedNotification`
     *
     * @param EE_Registration[] $registrations
     * @param string            $identifier
     * @throws EE_Error
     */
    protected function setRegistrationsProcessed(array $registrations, $identifier)
    {
        if ($registrations) {
            $this->commandBus()->execute(
                $this->registry->create(
                    'EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\RegistrationsNotifiedCommand',
                    array($registrations, $identifier)
                )
            );
        }
    }


    /**
     * Extracts the global group from an array of message template groups if the array has a global group.
     *
     * @param array $message_template_groups
     * @return EE_Message_Template_Group
     */
    protected function extractGlobalMessageTemplateGroup(array $message_template_groups)
    {
        $global_groups = array_filter(
            $message_template_groups,
            function (EE_Message_Template_Group $message_template_group) {
                return $message_template_group->is_global();
            }
        );
        //there should only be one global group, so we only handle one.
        return $global_groups ? reset($global_groups) : null;
    }


    /**
     * This retrieves the data for all the custom message template groups used for triggering the messages.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * return array An array of data for processing.
     * @throws EE_Error
     */
    abstract protected function getDataForCustomMessageTemplateGroups(array $message_template_groups);


    /**
     * This retrieves the data for the global message template group (if present).
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @param array                       $data
     * @return array
     * @throws EE_Error
     */
    abstract protected function getDataForGlobalMessageTemplateGroup(array $message_template_groups, array $data);


    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     *
     * @param array $data
     */
    abstract protected function process(array $data);
}
