<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\message;

use DomainException;
use EE_Error;
use EE_Form_Section_Proper;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\entities\message\SchedulingSettings;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use EE_Message_Template_Group;
use EE_Registry;
use EE_No_Layout;
use EventEspresso\core\libraries\form_sections\strategies\filter\VsprintfFilter;
use EE_Text_Input;
use EE_Int_Validation_Strategy;
use EE_Int_Normalization;
use InvalidArgumentException;
use LogicException;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use ReflectionException;

/**
 * Form (and handler) for the scheduling metabox content.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage domain\services\admin
 * @author     Darren Ethier
 * @since      1.0.0
 */
class SchedulingMetaboxFormHandler extends FormHandler
{

    /**
     * @var EE_Message_Template_Group;
     */
    protected $message_template_group;

    /**
     * @var SchedulingSettings;
     */
    protected $scheduling_settings;


    /**
     * This is whatever message template context the view is for.
     *
     * @var string
     */
    protected $context;


    /**
     * SchedulingMetaboxFormHandler constructor.
     *
     * @param EE_Message_Template_Group $message_template_group
     * @param EE_Registry               $registry
     * @param string                    $context
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     */
    public function __construct(EE_Message_Template_Group $message_template_group, EE_Registry $registry, $context)
    {
        $this->message_template_group = $message_template_group;
        $this->scheduling_settings = new SchedulingSettings($message_template_group);
        $this->context = $context;
        $label = esc_html__('Scheduling Settings', 'event_espresso');
        parent::__construct(
            $label,
            $label,
            'scheduling_settings',
            '',
            FormHandler::DO_NOT_SETUP_FORM,
            $registry
        );
    }


    /**
     * creates and returns the actual form
     *
     * @return EE_Form_Section_Proper
     * @throws LogicException
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function generate()
    {
        $this->setForm($this->getSchedulingForm());
        return $this->form();
    }


    /**
     * @param array $form_data
     * @return bool
     * @throws EE_Error
     * @throws InvalidFormSubmissionException
     * @throws LogicException
     * @throws ReflectionException
     */
    public function process($form_data = array())
    {
        $valid_data = (array) parent::process($form_data);
        if (empty($valid_data)) {
            return false;
        }

        $this->scheduling_settings->setCurrentThreshold(
            $valid_data[ Domain::META_KEY_DAYS_BEFORE_THRESHOLD ],
            $this->context
        );
        return true;
    }


    /**
     * Get the form for the metabox content
     *
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws ReflectionException
     * @throws InvalidInterfaceException
     */
    protected function getSchedulingForm()
    {
        return new EE_Form_Section_Proper(
            array(
                'name'             => 'messages_scheduling_settings',
                'html_id'          => 'messages-scheduling-settings',
                'html_class'       => 'form-table',
                'layout_strategy'  => new EE_No_Layout(array('use_break_tags' => false)),
                'form_html_filter' => new VsprintfFilter(
                    $this->getContentString(),
                    array('<p class="automated-message-scheduling-input-wrapper">', '<p>')
                ),
                'subsections'      => array(
                    Domain::META_KEY_DAYS_BEFORE_THRESHOLD => new EE_Text_Input(array(
                        'validation_strategies'  => new EE_Int_Validation_Strategy(),
                        'normalization_strategy' => new EE_Int_Normalization(),
                        'html_name'              => Domain::META_KEY_DAYS_BEFORE_THRESHOLD,
                        'html_label_text'        => '',
                        'default'                => $this->scheduling_settings->currentThreshold($this->context),
                    )),
                ),
            )
        );
    }


    /**
     * Return the correct content string for the metabox content based on what message type is for this view.
     *
     * @return string
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected function getContentString()
    {
        $message_type = $this->message_template_group->message_type();
        if ($message_type !== Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME
            && $message_type !== Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT
        ) {
            return esc_html__(
                'This metabox should only be displayed for Automated Upcoming Event or Automated Upcoming Datetime message type templates',
                'event_espresso'
            );
        }

        /**
         * Note because of the way the form is setup, the 3rd argument is the fully rendered html for the form. So we
         * need to make sure to exclude that from our format strings.
         */
        return $message_type === Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME
            ? esc_html(
                _n(
                    '%1$sSend notifications %4$s day before the datetime.%2$',
                    '%1$sSend notifications %4$s days before the datetime.%2$',
                    $this->scheduling_settings->currentThreshold($this->context),
                    'event_espresso'
                )
            )
            : esc_html(
                _n(
                    '%1$sSend notifications %4$s day before the event.%2$s',
                    '%1$sSend notifications %4$s days before the event.%2$s',
                    $this->scheduling_settings->currentThreshold($this->context),
                    'event_espresso'
                )
            );
    }
}
