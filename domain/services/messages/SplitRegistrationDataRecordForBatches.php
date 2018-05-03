<?php

namespace EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages;

/**
 * SplitRegistrationDataRecordForBatches
 * This service handles splitting up an array of registration records in the following format:
 * array(
 *   10 => array( 'REG_ID' => 10, 'ATT_ID' => 20, 'EVT_ID' => 123, 'TXN_ID' => 25 ),
 *   25 => array( 'REG_ID' => 25, 'ATT_ID' => 20, 'EVT_ID' => 564, 'TXN_ID' => 30 ),
 *   ...
 * )
 * ...into smaller batches of registrations not exceeding the provided max count for registrations.
 * The returned result for all methods in this service will be an array similar to this:
 * array(
 *    0 => array(
 *       10 => array( 'REG_ID' => 10, 'ATT_ID' => 20, 'EVT_ID' => 123, 'TXN_ID' => 25 ),
 *       25 => array( 'REG_ID' => 25, 'ATT_ID' => 20, 'EVT_ID' => 564, 'TXN_ID' => 30 ),
 *       //...
 *    ),
 *    1 => array(
 *       15 => array( 'REG_ID' => 15, 'ATT_ID' => 22, 'EVT_ID' => 123, 'TXN_ID' => 45 ),
 *       25 => array( 'REG_ID' => 30, 'ATT_ID' => 22, 'EVT_ID' => 564, 'TXN_ID' => 60 ),
 *       //...
 *    )
 * )
 * ...where each element in the array represents a "batch".  How the arrays get sorted is dependent on the method used.
 *
 * @package EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages
 * @author  Darren Ethier
 * @since   1.0.0
 */
class SplitRegistrationDataRecordForBatches
{
    /**
     * This will ensure that when possible each batch will contain all the registrations belonging to an attendee.
     * When the registration count for an attendee exceeds the batch limit, then they will be grouped by the event.
     * When they exceed the batch for an event, then they will be grouped by transaction.
     * Note: the batch limit is not what the count of registrations will be for each batch, but rather the limit of
     * registrations for each batch.  It's possible batches will be smaller than this count.
     * Note: the incoming format for the data expected is described in the class header doc. Any elements not matching
     * the expected format will be silently discarded.
     *
     * @param array $records registration records coming in the expected format.
     * @param int   $batch_limit
     * @return array    @see class doc header for example format of returned array.
     */
    public function splitDataByAttendeeId(array $records, $batch_limit)
    {
        $batch_limit = (int) $batch_limit;
        return $this->splitGroupedData(
            array(
                'ATT_ID',
                'EVT_ID',
                'TXN_ID',
            ),
            $records,
            array(),
            $batch_limit,
            $batch_limit
        );
    }


    /**
     * @see splitDataByAttendeeId.  This operates the same except each batch is first grouped by EventId and then
     * Transaction Id when trying to fill up each "batch" for the batch limit.
     * @param array $records
     * @param       $batch_limit
     * @return array
     */
    public function splitDataByEventId(array $records, $batch_limit)
    {
        $batch_limit = (int) $batch_limit;
        return $this->splitGroupedData(
            array(
                'EVT_ID',
                'TXN_ID',
            ),
            $records,
            array(),
            $batch_limit,
            $batch_limit
        );
    }


    /**
     * /**
     * @see splitDataByAttendeeId.  This operates the same except each batch is first grouped by Transaction ID and
     *                              this will not split up registrations across transactions.
     * @param array $records
     * @param       $batch_limit
     * @return array
     */
    public function splitDataByTransactionId(array $records, $batch_limit)
    {
        $batch_limit = (int) $batch_limit;
        return $this->splitGroupedData(
            array(
                'TXN_ID',
            ),
            $records,
            array(),
            $batch_limit,
            $batch_limit
        );
    }


    /**
     * The batching helpers here will return batches indexed via an assembled string ('PREFIX_{ID}') so that array
     * concatenation works as expected (works differently on associative vs numerically indexed arrays).  This helper
     * method allows you to convert the indexes back to their original indexes.
     *
     * @param array  $values_to_modify
     * @param string $key_prefix
     * @return array
     */
    public function convertStringIndexesToIdFor(array $values_to_modify, $key_prefix = 'REG_ID_')
    {
        $modified_values = array();
        foreach ($values_to_modify as $key_to_modify => $value) {
            $key_to_modify = str_replace($key_prefix, '', $key_to_modify);
            $key_to_modify = (int) $key_to_modify;
            $modified_values[ $key_to_modify ] = $value;
        }
        return $modified_values;
    }


    /**
     * Method intended to use recursively create batches from the given data and grouping methods.
     *
     * @param array $keys_to_group_by These are the various keys to recurse through when fitting records into a batch.
     * @param array $records
     * @param array $current_batch
     * @param int   $batch_remaining
     * @param int   $batch_limit      The max that can be in a batch.
     * @param array $batches          The assembled batches array.
     * @param int   $batch_count      The current count for the batch.
     * @return array
     */
    protected function splitGroupedData(
        array $keys_to_group_by,
        array $records,
        array $current_batch,
        $batch_remaining,
        $batch_limit,
        array $batches = array(),
        $batch_count = 1
    ) {
        // shift off the first method for use.
        $key_to_group_by = array_shift($keys_to_group_by);
        $leftover_threshold = $this->getLeftoverThreshold();
        foreach ($this->groupRecordsBy($key_to_group_by, $records) as $registration_records_grouped) {
            $batch_index = 'B_' . $batch_count;
            if (count($registration_records_grouped) <= $batch_remaining) {
                $batch_remaining -= count($registration_records_grouped);
                $current_batch += $registration_records_grouped;
                // if $batch_remaining is less than the leftover threshold., then let's just start a new batch anyways, to save some looping.
                if ($batch_remaining < $leftover_threshold) {
                    $batches[ $batch_index ] = $current_batch;
                    $current_batch = array();
                    $batch_remaining = $batch_limit;
                    $batch_count++;
                }
                continue;
            }
            // if there's no more methods to recurse, then append current batch to the batches and continue.
            // Essentially this means that we've grouped the data into as small of chunks as we can so if there's no more
            // room then we start a new batch with the current data set and continue.
            if (empty($keys_to_group_by)) {
                $batches[ $batch_index ] = $current_batch;
                $current_batch = $registration_records_grouped;
                $batch_remaining -= $batch_limit - count($registration_records_grouped);
                // if $batch_remaining is less than 10, then let's just start a new batch anyways, to save some looping.
                if ($batch_remaining < $leftover_threshold) {
                    $batches[ $batch_index ] = $current_batch;
                    $current_batch = array();
                    $batch_remaining = $batch_limit;
                    $batch_count++;
                }
                continue;
            }

            // recurse
            /** @noinspection AdditionOperationOnArraysInspection */
            $batches += $this->splitGroupedData(
                $keys_to_group_by,
                $registration_records_grouped,
                $current_batch,
                $batch_remaining,
                $batch_limit,
                $batches,
                $batch_count
            );

            // pop off the last element in the batch if it's under our batch threshold then assign it to $current_batch.
            // if its not under our batch threshold then we'll leave things reset for a new batch.
            $current_batch = array_pop($batches);
            $batch_remaining = $batch_limit - count($current_batch);
            $batch_count = count($batches);
            $batch_count = $batch_count === 0 ? 2 : $batch_count + 1;
            $batch_index = 'B_' . $batch_count;
            if ($batch_remaining < $leftover_threshold) {
                $batches[ $batch_index ] = $current_batch;
                $current_batch = array();
                $batch_remaining = $batch_limit;
                $batch_count++;
            }
        }
        if (! empty($current_batch)) {
            $batch_count = isset($batch_count)
                ? $batch_count
                : count($batches) + 1;
            $batch_index = isset($batch_index)
                ? $batch_index
                : 'B_' . $batch_count;
            $batches[ $batch_index ] = $current_batch;
        }
        return $batches;
    }


    /**
     * Receives the array of registration records and groups them by the given key.
     * Returns an array in the following format (example is if grouped by ATT_ID):
     * array(
     *     10 => array(
     *          15 => array( 'REG_ID' => 15, 'ATT_ID' => 10, 'EVT_ID' => 20, 'TXN_ID' => 34 ),
     *          20 => array( 'REG_ID' => 20, 'ATT_ID' => 10, 'EVT_ID' => 30, 'TXN_ID' => 50 ),
     *     ),
     *     25 => array(
     *          36 => array( 'REG_ID' => 36, 'ATT_ID' => 25, 'EVT_ID' => 20, 'TXN_ID' => 67 ),
     *          37 => array( 'REG_ID' => 37, 'ATT_ID' => 25, 'EVT_ID' => 40, 'TXN_ID' => 19 ),
     *     )
     * );
     * The array will be sorted by the element with the highest number of registrations first.
     *
     * @param string $key_for_grouping_by
     * @param array  $records
     * @return array
     */
    protected function groupRecordsBy($key_for_grouping_by, array $records)
    {
        $grouped = array();
        foreach ($records as $registration_id => $record) {
            if (isset($record[ $key_for_grouping_by ])) {
                // so usort doesn't mess things up index wise.
                $index = $key_for_grouping_by . '_' . $record[ $key_for_grouping_by ];
                $registration_index = strpos($registration_id, 'REG_ID_') === false
                    ? 'REG_ID_' . $registration_id
                    : $registration_id;
                $grouped[ $index ][ $registration_index ] = $record;
            }
        }
        return $this->sortByCountOfRecordsDescending($grouped);
    }


    /**
     * Receives an array of registration records that have been grouped and sorts them from the largest group to the
     * smallest group.
     *
     * @param array $records
     * @return array
     */
    protected function sortByCountOfRecordsDescending(array $records)
    {
        $this->customStableSort($records, function ($record1, $record2) {
            $record1_count = count($record1);
            $record2_count = count($record2);
            if ($record1_count === $record2_count) {
                return 0;
            }
            return ($record1_count > $record2_count) ? -1 : 1;
        });
        return $records;
    }


    /**
     * This is a utility function that works around the fact that PHP versions prior to PHP 7 might not preserve array
     * position on items sorted where values are equal.  This ensures that when values are equal their position is
     * preserved.
     *
     * @see https://github.com/vanderlee/PHP-stable-sort-functions/blob/master/classes/StableSort.php where code was
     *      obtained.
     * @param array $items_to_sort
     * @param       $value_compare_function
     * @return bool
     */
    protected function customStableSort(array &$items_to_sort, $value_compare_function)
    {
        $index = 0;
        foreach ($items_to_sort as &$item) {
            $item = array($index++, $item);
        }
        unset($item);
        $result = uasort($items_to_sort, function ($a, $b) use ($value_compare_function) {
            $result = $value_compare_function($a[1], $b[1]);
            return $result === 0 ? $a[0] - $b[0] : $result;
        });
        /** @noinspection ReferenceMismatchInspection */
        foreach ($items_to_sort as &$item) {
            $item = $item[1];
        }
        unset($item);
        return $result;
    }


    /**
     * When splitting items, to prevent extra looping, we have a check for how many remaining spots are available in a
     * batch and if its within a certain number, then we start the next batch.  This has been labelled the
     * "leftOverThreshold".
     *
     * @return int
     */
    protected function getLeftoverThreshold()
    {
        return apply_filters(
            'FHEE__EventEspresso_AutomatedEventNotifications_domain_services_messages_SplitRegistrationDataRecordForBatches__getLeftoverThreshold',
            10
        );
    }
}
