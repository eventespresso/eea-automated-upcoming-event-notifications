<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\includes\AddonTestCase;
use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\SplitRegistrationDataRecordForBatchesMock;

/**
 * SplitRegistrationDataRecordForBatchesTest
 *
 * @group   newtest
 * @author  Darren Ethier
 * @since   1.0.0
 */
class SplitRegistrationDataRecordForBatchesTest extends AddonTestCase
{

    /**
     * @var SplitRegistrationDataRecordForBatchesMock
     */
    private $service_mock;


    public function setUp()
    {
        parent::setUp();
        $this->service_mock = new SplitRegistrationDataRecordForBatchesMock();
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->service_mock = null;
    }


    public function splitDataByTransactionProvider()
    {
        //keep in mind that the "leftoverThreshold" is set at 2 for tests.  So that means that you may see some
        //unexpected counts for some of the batch limits being tested as outlined here in the expectation values.
        return array(
            'batch limit 2' => array(
                $this->getSampleDataSet(),
                2,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'B_2' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                    ),
                    'B_3' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'B_4' => array(
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                    'B_5' => array(
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    ),
                )
            ),
            'batch limit 4' => array(
                $this->getSampleDataSet(),
                4,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                    ),
                    'B_2' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5),
                    ),
                    'B_3' => array(
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    ),
                )
            ),
            'batch limit 8' => array(
                $this->getSampleDataSet(),
                8,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5),
                    ),
                    'B_2' => array(
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    )
                )
            )
        );
    }



    public function splitDataByEventProvider()
    {
        //keep in mind that the "leftoverThreshold" is set at 2 for tests.  So that means that you may see some
        //unexpected counts for some of the batch limits being tested as outlined here in the expectation values.
        return array(
            'batch limit 6' => array(
                $this->getSampleDataSet(),
                6,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'B_2' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    )
                )
            ),
            'batch limit 4' => array(
                $this->getSampleDataSet(),
                4,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'B_2' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'B_3' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    )
                )
            ),
            'batch limit 8' => array(
                $this->getSampleDataSet(),
                8,
                array(
                    'B_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                    ),
                    'B_2' => array(
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    )
                )
            )
        );
    }



    public function splitDataByAttendeeProvider()
    {
        //keep in mind that the "leftoverThreshold" is set at 2 for tests.  So that means that you may see some
        //unexpected counts for some of the batch limits being tested as outlined here in the expectation values.
        return array(
            'batch limit 5' => array(
                $this->getSampleDataSet(),
                5,
                array(
                    'B_1' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'B_2' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5),
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    )
                )
            ),
            'batch limit 3' => array(
                $this->getSampleDataSet(),
                3,
                array(
                    'B_1' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'B_2' => array(
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                    'B_3' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    )
                )
            ),
            'batch_limit 4' => array(
                $this->getSampleDataSet(),
                4,
                array(
                    'B_1' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'B_2' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                    'B_3' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    )
                )
            )
        );
    }



    public function groupRecordsProvider()
    {
        return array(
            'ATT_ID Group' => array(
                'ATT_ID',
                $this->getSampleDataSet(),
                array(
                    'ATT_ID_21' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'ATT_ID_20' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                    'ATT_ID_22' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    ),
                )
            ),
            'EVT_ID Group' => array(
                'EVT_ID',
                $this->getSampleDataSet(),
                array(
                    'EVT_ID_30' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'EVT_ID_31' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'EVT_ID_32' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                )
            ),
            'TXN_ID Group' => array(
                'TXN_ID',
                $this->getSampleDataSet(),
                array(
                    'TXN_ID_1' => array(
                        'REG_ID_15' => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
                        'REG_ID_22' => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
                    ),
                    'TXN_ID_2' => array(
                        'REG_ID_16' => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
                        'REG_ID_20' => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
                    ),
                    'TXN_ID_3' => array(
                        'REG_ID_17' => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
                        'REG_ID_21' => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
                    ),
                    'TXN_ID_5' => array(
                        'REG_ID_19' => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
                        'REG_ID_23' => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
                    ),
                    'TXN_ID_4' => array(
                        'REG_ID_18' => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
                    ),
                )
            )
        );
    }


    /**
     * @dataProvider groupRecordsProvider
     * @param string $key
     * @param array $sample_data_set
     * @param array $expected_result
     * @throws \PHPUnit\Framework\Exception
     */
    public function testGroupRecordsBy($key, array $sample_data_set, array $expected_result)
    {
        $actual_result = $this->service_mock->groupRecordsBy($key, $sample_data_set);

        //count of $expected result should match count of actual result
        $this->assertCount(count($expected_result), $actual_result);

        //the first result in $expected should match the first result in $actual.
        $this->assertSame(reset($expected_result), reset($actual_result));

        //if we loop through each inner array value on actual, that should match the corresponding inner value count of
        //expected
        foreach ($actual_result as $actual_result_key => $inner_records) {
            $this->assertCount(count($inner_records), $expected_result[$actual_result_key]);
            //the REG_IDs in the inner values should match the reg_ids in the corresponding expected result.
            $this->assertSame(array_keys($inner_records), array_keys($expected_result[$actual_result_key]));
        }
    }


    /**
     * @dataProvider splitDataByAttendeeProvider
     * @param array $records
     * @param       $batch_limit
     * @param array $expected_result
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSplitDataByAttendeeId(array $records, $batch_limit, array $expected_result)
    {
        $actual_result = $this->service_mock->splitDataByAttendeeId($records, $batch_limit);
        $this->assertCount(count($expected_result), $actual_result);
        $this->assertSame(reset($expected_result), reset($actual_result));
    }


    /**
     * @dataProvider splitDataByEventProvider
     * @param array $records
     * @param       $batch_limit
     * @param array $expected_result
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSplitDataByEventId(array $records, $batch_limit, array $expected_result)
    {
        $actual_result = $this->service_mock->splitDataByEventId($records, $batch_limit);
        $this->assertCount(count($expected_result), $actual_result);
        $this->assertSame(reset($expected_result), reset($actual_result));
    }



    /**
     * @dataProvider splitDataByTransactionProvider
     * @param array $records
     * @param       $batch_limit
     * @param array $expected_result
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSplitDataByTransactionId(array $records, $batch_limit, array $expected_result)
    {
        $actual_result = $this->service_mock->splitDataByTransactionId($records, $batch_limit);
        $this->assertCount(count($expected_result), $actual_result);
        $this->assertSame(reset($expected_result), reset($actual_result));
    }


    /**
     * Note: if you change any of the data in this data set (including modifying order and/or adding new records) then
     * you'll also need to update the data providers:
     * - splitDataByTransactionProvider
     * - splitDataByEventProvider
     * - splitDataByAttendeeProvider
     * - groupRecordsProvider
     * @return array
     */
    private function getSampleDataSet()
    {
        return array(
            15 => array('REG_ID' => 15, 'ATT_ID' => 20, 'EVT_ID' => 30, 'TXN_ID' => 1),
            16 => array('REG_ID' => 16, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 2),
            17 => array('REG_ID' => 17, 'ATT_ID' => 22, 'EVT_ID' => 32, 'TXN_ID' => 3),
            18 => array('REG_ID' => 18, 'ATT_ID' => 22, 'EVT_ID' => 30, 'TXN_ID' => 4),
            19 => array('REG_ID' => 19, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 5),
            20 => array('REG_ID' => 20, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 2),
            21 => array('REG_ID' => 21, 'ATT_ID' => 21, 'EVT_ID' => 31, 'TXN_ID' => 3),
            22 => array('REG_ID' => 22, 'ATT_ID' => 21, 'EVT_ID' => 30, 'TXN_ID' => 1),
            23 => array('REG_ID' => 23, 'ATT_ID' => 20, 'EVT_ID' => 32, 'TXN_ID' => 5)
        );
    }
}