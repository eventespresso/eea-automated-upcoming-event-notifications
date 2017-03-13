<?php
namespace EventEspresso\AutomatedEventNotifications\core\messages\admin;

use WP_Screen;
use LogicException;
use EE_Registry;
use EEM_Message_Template_Group;
use EE_Message_Template_Group;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');


/**
 * This sets up the metabox and handling of update for the message template settings for the new message types.
 * It should only be loaded on the message editor route.
 *
 * Due to this being a class used for hooking in functionality that should only run ONCE, this is instantiated
 * as a singleton.  This allows the instance to be exposed for de-registering if needed.
 *
 * Developers, do not use this class in your code.  This is likely going to be replaced at some point by an interface in
 * EE core for having message template group custom settings that add-ons like this one can hook into. We're not sure
 * what the api for that will look like yet so we're observing what patterns occur as we implement various message
 * template groups with extra settings.
 *
 * @package    EventEspresso\AutomatedEventNotifications
 * @subpackage \core\messages\admin
 * @author     Darren Ethier
 * @since      1.0.0
 */
class CustomTemplateSettings
{


    /**
     * Holds an instance of this class
     * @var CustomTemplateSettings
     */
    private static $instance;


    /**
     * CustomTemplateSettings constructor.
     *
     */
    private function __construct()
    {
        if (! EE_Registry::instance()->load_core('Request')->get('noheader', false)) {
            add_action('FHEE__EE_Admin_Page___load_page_dependencies__after_load', function () {
                $screen = get_current_screen();
                if ($screen instanceof WP_Screen) {
                    add_meta_box(
                        __CLASS__ . '_scheduling',
                        esc_html__('Scheduling Settings', 'event_espresso'),
                        array($this, 'schedulingMetabox'),
                        $screen->id, //not needed because we're already restricting to specific route
                        'side',
                        'high'
                    );
                }
            });
        }
        add_action(
            'AHEE__EEM_Base__update__begin',
            array($this, 'updateScheduling'),
            20,
            3
        );
    }


    /**
     * Used to instantiate (only once) and return an instance of the CustomTemplateSettings
     *
     * @param \WP_Screen $screen
     * @return CustomTemplateSettings|null
     */
    public static function instance()
    {
        if (! self::$instance instanceof CustomTemplateSettings) {
            $request = EE_Registry::instance()->load_class('Request');
            if ($request->get('page', false) !== 'espresso_messages'
                || (
                    $request->get('action', '') !== 'update_message_template'
                    && $request->get('action', '') !== 'edit_message_template'
                )
            ) {
                //get out because we don't want this loading on this page.
                return null;
            }
            self::$instance = new self;
        }
        return self::$instance;
    }


    public function schedulingMetabox()
    {
        $message_template_group = $this->messageTemplateGroup();
        if (! $message_template_group instanceof EE_Message_Template_Group) {
            echo esc_html__(
                'There was a problem with generating the content for this metabox, please refresh your page.',
                'event_espresso'
            );
        }
        $message_type = $message_template_group->message_type();
        $scheduling_threshold = (int) $message_template_group->get_extra_meta('ee_notification_threshold', true, 5);
        if ((
                $message_type !== 'automate_upcoming_datetime'
                && $message_type !== 'automate_upcoming_event'
            )
        ) {
            echo esc_html__(
                'This metabox should only be displayed for Automated Upcoming Event or Automated Upcoming Datetime '
                . 'message type templates',
                'event_espresso'
            );
        }
        $scheduling_threshold_text_format = $message_type === 'automate_upcoming_datetime'
            ? esc_html(
                _n(
                    'Send notifications %s day before the datetime.',
                    'Send notifications %s days before the datetime.',
                    $scheduling_threshold,
                    'event_espresso'
                )
            )
            : esc_html(
                _n(
                    'Send notifications %s day before the event.',
                    'Send notifications %s days before the event.',
                    $scheduling_threshold,
                    'event_espresso'
                )
            );
        $content = $this->schedulingThresholdContent(
            $scheduling_threshold,
            $scheduling_threshold_text_format
        );
        $content .= $this->schedulingDisabledContent(
            $message_template_group->get_extra_meta('ee_notification_threshold', true, true)
        );
        echo $content;
    }




    private function messageTemplateGroup($id = 0)
    {
        //if the id is 0 then let's attempt to get from request
        $id = $id
            ? $id
            : EE_Registry::instance()->load_core('Request')->get('id');
        return EEM_Message_Template_Group::instance()->get_one_by_ID($id);
    }



    private function schedulingThresholdContent($current_threshold, $format_string)
    {
        $text_input = '<input value="'
                      . $current_threshold
                      . '" name="ee_notification_threshold" id="ee_notification_threshold" class="ee-medium-text"'
                      . ' type="number" min=1>';
        return '<p>' . sprintf($format_string, $text_input) . '</p>';
    }


    private function schedulingDisabledContent($disabled)
    {
        $select_input = '<select id="ee-notification-disabled" name="ee_notification_disabled">'
                            . '<option value="1"' . ((int) $disabled === 0 ? ' selected' : '') . '>'
                                . __('On', 'event_espresso')
                            . '</option>'
                            . '<option value="0"' . ((int) $disabled === 1 ? ' selected' : '') . '>'
                                . __('Off', 'event_espresso')
                            . '</option>'
                        . '</select>';
        return '<p>'
               . sprintf(
                   esc_html__('%1$sCurrently this message type is:%2$s %3$s', 'event_espresso'),
                   '<label for="ee-notification-disabled">',
                   $select_input,
                   '</label>'
               )
               . '</p>';
    }


    /**
     * Returns the scheduling form for the object for the scheduling input
     * @param \EE_Message_Template_Group $message_template_group
     * @return \EE_Form_Section_Proper
     */
    private function schedulingForm(EE_Message_Template_Group $message_template_group)
    {
        /**
         * @todo waiting on decision on how this gets done before it can be implemented
         * @link https://events.codebasehq.com/projects/event-espresso/tickets/10580
         */
        /*return new EE_Form_Section_Proper(
            array(
                'name' => 'ee_automated_message_scheduling',
                'html_id' => 'ee_automated_message_scheduling',
                'html_class' => 'form-table',
                'layout_strategy' => new EE_Template_Layout(
                    array(
                        'subsection_template_file' => EE_AUTOMATED_UPCOMING_EVENT_NOTIFICATION_PATH
                                                      . 'core/templates/ee_automated_message_scheduling_input.template.php',
                        'template_args' => array(
                            'format' => esc_html__('%1$sSend notifications %2$s days before the Datetime%3$s'),
                            'values' => array(
                                '<p>',
                                new EE_Text_Input(array(
                                    'validation_strategies' => new EE_Int_Validation_Strategy(),
                                    'normalization_strategy' => new EE_Int_Normalization(),
                                    'html_name' => 'ee_notification_threshold',
                                    'html_label_text' => '',
                                    'class' => 'ee-notification-threshold',
                                    'default' => $message_template_group->get_extra_meta('ee_notification_threshold', 5)
                                )),
                                '</p>'
                            )
                        )
                    )
                )
            )
        );/**/
    }


    public function updateScheduling($model, $fields_n_values, $query_params)
    {
        /**
         * @todo need to implement filter_input here because I can explicitly require from the post data.
         */
        $request = EE_Registry::instance()->load_core('Request');
        $where_params = is_array($query_params) && isset($query_params[0]) && is_array($query_params[0])
            ? $query_params[0]
            : array();
        $GRP_ID = isset($where_params['GRP_ID'])
            ? $where_params['GRP_ID']
            : 0;
        if (! $model instanceof EEM_Message_Template_Group
            || ! $GRP_ID
        ) {
            return;
        }
        //can we get the object for this?
        $message_template_group = EEM_Message_Template_Group::instance()->get_one_by_ID($GRP_ID);

        //get out if this update doesn't apply (because it means it hasn't been saved yet and we don't have an id for
        //the model object)  In our scenario this is okay because user's will only ever see an already
        //created message template group in the ui
        if (! $message_template_group instanceof EE_Message_Template_Group
            || (
                $message_template_group->message_type() !== 'automate_upcoming_datetime'
                && $message_template_group->message_type() !== 'automate_upcoming_event'
            )
            //yes this intentionally will catch if someone sets the value to 0 because 0 is not allowed.
            || ! $request->get('ee_notification_threshold', false)
            || $request->get('ee_notification_disabled', null) === null
        ) {
            return;
        }
        $message_template_group->update_extra_meta(
            'ee_notification_threshold',
            (int) $request->get('ee_notification_threshold')
        );
        $message_template_group->update_extra_meta(
            'ee_notification_disabled',
            filter_var($request->get('ee_notification_disabled'), FILTER_VALIDATE_BOOLEAN)
        );
    }
}