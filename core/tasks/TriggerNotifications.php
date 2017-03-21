<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\tasks;

use EE_Message_Template_Group;
use EE_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');

abstract class TriggerNotifications
{


    const REGISTRATION_TRACKER_PREFIX = 'ee_auen_processed_';

    /**
     * @var EE_Message_Template_Group[]
     */
    protected $message_template_groups;


    /**
     * If the incoming groups includes the global message template group for the batch, it will be stored here.
     * @var EE_Message_Template_Group
     */
    protected $global_message_template_group;


    /**
     * TriggerNotifications constructor.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     */
    public function __construct(array $message_template_groups)
    {
        $this->message_template_groups = $this->setupAndExtractGlobalGroup($message_template_groups);

        //if the message_template_groups and the global_message_template_group is empty, then there is nothing to do.
        if (empty($this->message_template_groups) && empty($this->global_message_template_group)) {
            return;
        }
    }


    /**
     * This filters the incoming array so its only an array of message template groups and then extracts the global
     * group if its present.
     *
     * @param EE_Message_Template_Group[] $message_template_groups
     * @return EE_Message_Template_Group[]
     */
    private function setupAndExtractGlobalGroup($message_template_groups)
    {
        //first make sure we only have instances of EE_Message_Template_Group in this array.
        $only_message_template_groups = array_filter(
            $message_template_groups,
            function ($message_template_group) {
                return $message_template_group instanceof EE_Message_Template_Group;
            }
        );
        if (empty($only_message_template_groups)) {
            return array();
        }
        //is there a global template group?
        $contains_global = array_filter(
            $only_message_template_groups,
            function (EE_Message_Template_Group $message_template_group) {
                return $message_template_group->is_global();
            }
        );

        //if there was a global template group, extract from the main array and set the global to its designated
        //property.
        if (! empty($contains_global)) {
            $key_for_global = reset(array_flip($contains_global));
            //unset from original array
            unset($only_message_template_groups[$key_for_global]);
            //assign to property
            $this->global_message_template_group = reset($contains_global);
        }
        return $only_message_template_groups;
    }


    /**
     * Use to save the flag indicating the registration has received a notification from being triggered.
     * @param \EE_Registration $registration
     * @param string           $id_ref
     * @return int|bool        @see EE_Base_Class::add_extra_meta
     */
    protected function setRegistrationProcessed(EE_Registration $registration, $id_ref)
    {
        return $registration->update_extra_meta(
            self::REGISTRATION_TRACKER_PREFIX . $id_ref,
            true
        );
    }


    /**
     * Receives an array of registrations and calls `setRegistrationReceivedNotification` for each registration.
     * If you need the response from the setting of this value (success/fail) then its suggested you call
     * `setRegistrationReceivedNotification`
     *
     * @param EE_Registration[] $registrations
     * @param string            $id_ref
     */
    protected function setRegistrationsProcessed(array $registrations, $id_ref)
    {
        if ($registrations) {
            array_walk(
                $registrations,
                function ($registration) use ($id_ref) {
                    if (! $registration instanceof EE_Registration) {
                        return;
                    }
                    $this->setRegistrationProcessed($registration, $id_ref);
                }
            );
        }
    }


    /**
     * This executes the processing of messages that should get sent (if there are any conditions matching the criteria)
     * set by child classes.
     */
    public function run()
    {
        //okay we've got something, so let's get the data, and then process.
        $this->process($this->getData());
    }


    /**
     * This should handle the processing of provided data and the actual triggering of the messages.
     * @param mixed $data
     */
    abstract protected function process($data);


    /**
     * This should handle setting up the data that would be sent into the process method.
     * @return mixed
     */
    abstract protected function getData();
}
