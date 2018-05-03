<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands;

use EEM_Base;
use EventEspresso\core\services\commands\Command;

/**
 * ItemsNotifiedCommand
 * Command for tracking items (EE_Base_Class ids) that have been notified.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\commands
 * @author  Darren Ethier
 * @since   1.0.0
 */
class ItemsNotifiedCommand extends Command
{


    /**
     * @var EEM_Base
     */
    private $model;


    /**
     * @var array
     */
    private $ids;


    /**
     * This will be the message type context for which these items received notifications.
     *
     * @var string
     */
    private $context;


    /**
     * ItemsNotifiedCommand constructor.
     *
     * @param EEM_Base     $model
     * @param        array $ids
     * @param              $context
     */
    public function __construct(EEM_Base $model, array $ids, $context)
    {
        $this->model = $model;
        $this->ids = $this->validateItemIds($ids);
        $this->context = $context;
    }


    /**
     * Ensures the given array only contains integers that correspond with the model object and filters out integers
     * that don't correspond to the model object type.
     *
     * @param  array $ids
     * @return array
     */
    private function validateItemIds(array $ids)
    {
        // make sure these are all ints
        $ids = array_map(
            function ($id) {
                return (int) $id;
            },
            $ids
        );
        $model = $this->model;
        return array_filter(
            $ids,
            function ($item_id) use ($model) {
                return $model->exists_by_ID($item_id);
            }
        );
    }


    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }


    public function getModel()
    {
        return $this->model;
    }
}
