<?php
namespace EventEspresso\AutomateUpcomingEventNotificationsTests\mocks;

use EventEspresso\AutomatedUpcomingEventNotifications\domain\services\messages\SplitRegistrationDataRecordForBatches;

class SplitRegistrationDataRecordForBatchesMock extends SplitRegistrationDataRecordForBatches
{
    public function groupRecordsBy($key_for_grouping, array $records)
    {
        return parent::groupRecordsBy($key_for_grouping, $records);
    }



    public function getLeftoverThreshold()
    {
        return 2;
    }
}