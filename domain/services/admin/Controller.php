<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin;

use DomainException;
use EE_Registry;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use InvalidArgumentException;
use LogicException;
use WP_Screen;
use EEM_Message_Template_Group;
use EE_Message_Template_Group;
use EEM_Base;
use EE_Request;
use Exception;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\admin\message\SchedulingMetaboxFormHandler;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');


/**
 * This is the controller for things hooking into the EE admin for the addon.
 *
 * @package    EventEspresso\AutomatedUpcomingEventNotifications
 * @subpackage \domain\services\admin
 * @author     Darren Ethier
 * @since      1.0.0
 */
class Controller
{

    /**
     * @var SchedulingMetaboxFormHandler
     */
    protected $form_handler;


    /**
     * @var  EE_Request
     */
    protected $request;



    /**
     * Controller constructor.
     *
     * @param EE_Request $request
     */
    public function __construct(EE_Request $request)
    {
        $this->request = $request;

        if (! $this->canLoad()) {
            return;
        }
        if ($this->isDisplay()) {
            $this->registerMetaBox();
        }
        add_action(
            'AHEE__EEM_Base__update__begin',
            array($this, 'updateScheduling'),
            20,
            3
        );
    }



    /**
     * @return void
     */
    protected function registerMetaBox()
    {
        add_action('FHEE__EE_Admin_Page___load_page_dependencies__after_load', function () {
            $screen = get_current_screen();
            if ($screen instanceof WP_Screen) {
                add_meta_box(
                    __CLASS__ . '_scheduling',
                    esc_html__('Scheduling Settings', 'event_espresso'),
                    array($this, 'schedulingMetabox'),
                    $screen->id,
                    'side',
                    'high'
                );
            }
        });
    }


    /**
     * Used to instantiate (only once) and return an instance of the CustomTemplateSettings
     *
     * @return bool
     */
    protected function canLoad()
    {
        if ($this->request->get('page', false) !== 'espresso_messages'
            || (
                $this->request->get('action', '') !== 'update_message_template'
                && $this->request->get('action', '') !== 'edit_message_template'
            )
        ) {
            //get out because we don't want this loading on this request.
            return false;
        }

        //made to the first check, the next check is to make sure we're only adding this to editors for the new message
        //types
        $message_template_group = $this->messageTemplateGroup();
        return $message_template_group instanceof EE_Message_Template_Group
               && (
                   $message_template_group->message_type() === Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT
                   || $message_template_group->message_type() === Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME
               );
    }


    /**
     * Checks the request for the `noheader` parameter and if present then this is not for display so no need to
     * register things that only are used for display.
     *
     * @return bool
     */
    protected function isDisplay()
    {
        return ! $this->request->get('noheader', false);
    }


    /**
     * Callback for the `add_meta_box` function.
     * This provides the content for the scheduling metabox on the Automated Upcoming Datetime and Automated Upcoming
     * Event message type template editor.
     *
     * @throws EE_Error
     * @throws LogicException
     * @throws DomainException
     * @throws InvalidDataTypeException
     * @throws InvalidArgumentException
     */
    public function schedulingMetabox()
    {
        $scheduling_form = $this->messageTemplateGroup() instanceof EE_Message_Template_Group
            ? $this->schedulingForm($this->messageTemplateGroup())
            : null;
        if (! $scheduling_form instanceof SchedulingMetaboxFormHandler) {
            echo '<p class="ee-important">'
                 . esc_html__(
                     'There was a problem with generating the content for this metabox, please refresh your page.',
                     'event_espresso'
                 )
                 . '</p>';
        }
        echo $scheduling_form->display();
    }


    /**
     * Retrieve an instance of the Scheduling Form Handler
     *
     * @param EE_Message_Template_Group $message_template_group
     * @return SchedulingMetaboxFormHandler|null
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     */
    protected function schedulingForm(EE_Message_Template_Group $message_template_group)
    {
        return new SchedulingMetaboxFormHandler($message_template_group, EE_Registry::instance());
    }


    /**
     * Provides the message template group from either the given id or an id in the request.
     *
     * @param int $id
     * @return EE_Message_Template_Group
     */
    private function messageTemplateGroup($id = 0)
    {
        //if the id is 0 then let's attempt to get from request
        $id = $id
            ? $id
            : $this->request->get('id');
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return EEM_Message_Template_Group::instance()->get_one_by_ID(absint($id));
    }


    /**
     * Callback on the `AHEE__EEM_Base__update__begin` hook used to save any updates to the extra settings for the
     * message types (as controlled by the template editor).
     *
     * @param EEM_Base $model
     * @param array    $fields_n_values
     * @param array    $query_params
     */
    public function updateScheduling(EEM_Base $model, $fields_n_values, $query_params)
    {
        if (! $model instanceof EEM_Message_Template_Group) {
            return;
        }
        $where_params = is_array($query_params) && isset($query_params[0]) && is_array($query_params[0])
            ? $query_params[0]
            : array();
        $GRP_ID       = isset($where_params['GRP_ID'])
            ? $where_params['GRP_ID']
            : 0;
        if (! $GRP_ID) {
            return;
        }
        //can we get the object for this?
        $message_template_group = EEM_Message_Template_Group::instance()->get_one_by_ID($GRP_ID);

        //get out if this update doesn't apply (because it means it hasn't been saved yet and we don't have an id for
        //the model object)  In our scenario this is okay because user's will only ever see an already
        //created message template group in the ui
        if (! $message_template_group instanceof EE_Message_Template_Group
            //yes this intentionally will catch if someone sets the value to 0 because 0 is not allowed.
            || ! $this->request->get(Domain::META_KEY_DAYS_BEFORE_THRESHOLD, false)
            || (
                $message_template_group->message_type() !== Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_DATETIME
                && $message_template_group->message_type() !== Domain::MESSAGE_TYPE_AUTOMATE_UPCOMING_EVENT
            )
        ) {
            return;
        }

        //we're here so let's just load the form and allow it to process.
        try {
            if ($form = $this->schedulingForm($message_template_group)) {
                $form->process(
                    array(
                        Domain::META_KEY_DAYS_BEFORE_THRESHOLD => $this->request->get(
                            Domain::META_KEY_DAYS_BEFORE_THRESHOLD
                        ),
                        Domain::META_KEY_AUTOMATION_ACTIVE     => $this->request->get(
                            Domain::META_KEY_AUTOMATION_ACTIVE
                        ),
                    )
                );
            }
        } catch (Exception $e) {
            EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
        }
    }
}
