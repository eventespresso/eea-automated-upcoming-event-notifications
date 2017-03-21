<?php
namespace EventEspresso\AutomatedUpcomingEventNotifications\core\messages\admin;

use EE_Form_Section_Proper;
use EventEspresso\AutomatedUpcomingEventNotifications\core\entities\SchedulingSettings;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use EE_Message_Template_Group;
use EE_Registry;
use EE_No_Layout;
use EventEspresso\core\libraries\form_sections\strategies\filter\VsprintfFilter;
use EE_Text_Input;
use EE_Int_Validation_Strategy;
use EE_Int_Normalization;
use EE_Form_Section_HTML;
use EE_Select_Input;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access.');


/**
 * Form (and handler) for the scheduling metabox content.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage core\messages\admin
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
     * SchedulingMetaboxFormHandler constructor.
     *
     * @param \EE_Message_Template_Group $message_template_group
     * @param \EE_Registry               $registry
     */
    public function __construct(EE_Message_Template_Group $message_template_group, EE_Registry $registry)
    {
        $this->message_template_group = $message_template_group;
        $this->scheduling_settings = new SchedulingSettings($message_template_group);
        $label = esc_html__('Scheduling_Settings');
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
     */
    public function generate()
    {
        $this->setForm($this->getSchedulingForm());
    }


    /**
     * @param array $form_data
     * @return bool
     */
    public function process($form_data = array())
    {
        $valid_data = (array) parent::process($form_data);
        if (empty($valid_data)) {
            return false;
        }

        $this->scheduling_settings->setCurrentThreshold($valid_data[SchedulingSettings::DAYS_BEFORE_THRESHOLD]);
        $this->scheduling_settings->setIsActive($valid_data[SchedulingSettings::AUTOMATION_ACTIVE]);
        return true;
    }


    /**
     * Get the form for the metabox content
     * @return \EE_Form_Section_Proper
     */
    protected function getSchedulingForm()
    {
        return new EE_Form_Section_Proper(
            array(
                'name' => 'messages_scheduling_settings',
                'html_id' => 'messages-scheduling-settings',
                'html_class' => 'form-table',
                'layout_strategy' => new EE_No_Layout(array('use_break_tags' => false)),
                'form_html_filter' => new VsprintfFilter(
                    esc_html(
                        _n(
                            '%1$sSend notifications %2$s day before the datetime.%2$s%1$s%$3s%2$s',
                            '%1$sSend notifications %2$s days before the datetime.%2$s%1$s%$3s%2$s',
                            $this->scheduling_settings->currentThreshold(),
                            'event_espresso'
                        )
                    ),
                    array()
                ),
                'subsections' => array(
                    'opening_paragraph_tag' => new EE_Form_Section_HTML(
                        '<p class="automated-message-scheduling-input-wrapper">'
                    ),
                    SchedulingSettings::DAYS_BEFORE_THRESHOLD => new EE_Text_Input(array(
                        'validation_strategies' => new EE_Int_Validation_Strategy(),
                        'normalization_strategy' => new EE_Int_Normalization(),
                        'html_name' => SchedulingSettings::DAYS_BEFORE_THRESHOLD,
                        'html_label_text' => '',
                        'default' => $this->scheduling_settings->currentThreshold()
                    )),
                    'closing_paragraph_tag' => new EE_Form_Section_HTML('</p>'),
                    SchedulingSettings::AUTOMATION_ACTIVE => new EE_Select_Input(
                        array(
                            true => esc_html__('On', 'event_espresso'),
                            false => esc_html__('Off', 'event_espresso')
                        ),
                        array(
                            'html_name' => SchedulingSettings::AUTOMATION_ACTIVE,
                            'html_label_text' => esc_html__('Scheduling for this template is:', 'event_espresso'),
                            'default' => $this->scheduling_settings->isActive()
                        )
                    )
                )
            )
        );
    }


    /**
     * Return the correct content string for the metabox content based on what message type is for this view.
     * @return string
     */
    protected function getContentString()
    {
        $message_type = $this->message_template_group->message_type();
        if ((
            $message_type !== 'automate_upcoming_datetime'
            && $message_type !== 'automate_upcoming_event'
        )
        ) {
            return esc_html__(
                'This metabox should only be displayed for Automated Upcoming Event or Automated Upcoming Datetime '
                . 'message type templates',
                'event_espresso'
            );
        }

        return $message_type === 'automate_upcoming_datetime'
            ? esc_html(
                _n(
                    '%1$sSend notifications %2$s day before the datetime.%2$s%1$s%$3s%2$s',
                    '%1$sSend notifications %2$s days before the datetime.%2$s%1$s%$3s%2$s',
                    $this->scheduling_settings->currentThreshold(),
                    'event_espresso'
                )
            )
            : esc_html(
                _n(
                    '%1$sSend notifications %2$s day before the event.%2$s%1$s%$3s%2$s',
                    '%1$sSend notifications %2$s days before the event.%2$s%1$s%$3s%2$s',
                    $this->scheduling_settings->currentThreshold(),
                    'event_espresso'
                )
            );
    }
}