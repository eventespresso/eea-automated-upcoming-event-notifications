<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EEM_Base;
use EEM_Extra_Meta;
use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\core\services\commands\CommandInterface;
use EE_Error;
use EventEspresso\AutomatedUpcomingEventNotifications\domain\Domain;

/**
 * ItemsNotifiedCommandHandler
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands\event
 * @author  Darren Ethier
 * @since   1.0.0
 */
class ItemsNotifiedCommandHandler extends CommandHandler
{

    /**
     * @var EEM_Extra_Meta
     */
    private $extra_meta_model;


    /**
     * EventsNotifiedCommandHandler constructor.
     *
     * @param EEM_Extra_Meta $extra_meta_model
     */
    public function __construct(EEM_Extra_Meta $extra_meta_model)
    {
        $this->extra_meta_model = $extra_meta_model;
    }

    /**
     * @param CommandInterface|ItemsNotifiedCommand $command
     * @return int  count of items processed.
     * @throws EE_Error
     */
    public function handle(CommandInterface $command)
    {
        return $this->setItemsProcessed(
            $command->getIds(),
            $command->getModel(),
            $command->getContext()
        );
    }


    /**
     * Receives an array of event_ids sets them as processed.
     *
     * @param array    $item_ids
     * @param EEM_Base $model
     * @param string   $context           Represents the message type context for which these events are being
     *                                    processed for.
     * @return int count of events successfully processed.
     * @throws EE_Error
     */
    protected function setItemsProcessed(array $item_ids, EEM_Base $model, $context)
    {
        $count = 0;
        if ($item_ids) {
            $meta_key = $context === 'admin'
                ? Domain::META_KEY_PREFIX_ADMIN_TRACKER
                : Domain::META_KEY_PREFIX_REGISTRATION_TRACKER;
            $model_name = $model->get_this_model_name();
            // first let's see if there are already rows in the extra meta that have these ids.
            $already_notified_item_ids = $this->extra_meta_model->get_col(
                array(
                    array(
                        'EXM_key'  => $meta_key,
                        'OBJ_ID'   => array('IN', $item_ids),
                        'EXM_type' => $model_name,
                    ),
                ),
                'OBJ_ID'
            );

            if ($already_notified_item_ids) {
                $item_ids = array_diff($item_ids, $already_notified_item_ids);
            }

            // k now we should have a filtered list of item_ids to add the new meta record for.
            if ($item_ids) {
                foreach ($item_ids as $item_id) {
                    $success = $this->setItemProcessed($item_id, $meta_key, $model_name);
                    if ($success === false) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }


    /**
     * Use to save the flag indicating the item has received a notification.
     *
     * @param int    $item_id
     * @param string $meta_key   What meta_key is being used.
     * @param string $model_name What model the extra meta is for.
     * @return bool|int @see EEM_Base::insert
     * @throws EE_Error
     */
    protected function setItemProcessed($item_id, $meta_key, $model_name)
    {
        return $this->extra_meta_model->insert(
            array(
                'EXM_key'  => $meta_key,
                'OBJ_ID'   => $item_id,
                'EXM_type' => $model_name,
            )
        );
    }
}
