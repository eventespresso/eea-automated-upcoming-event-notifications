<?php

use EventEspresso\AutomateUpcomingEventNotificationsTests\mocks\SpecificDatetimeShortcodesMock;

class EeSpecificDatetimeShortcodesTest extends EE_UnitTestCase
{
    /**
     * @var SpecificDatetimeShortcodesMock
     */
    private $shortcode_library_mock;



    public function setUp()
    {
        parent::setUp();
        $this->shortcode_library_mock = new SpecificDatetimeShortcodesMock();
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->shortcode_library_mock = null;
    }



    private function getDataForTests()
    {
        /** @var EE_Registration $registration */
        $registration = $this->factory->registration_chained->create();

        $datetime = $registration->ticket()->get_first_related('Datetime');

        $shortcodes_and_expected_result = array(
            '[SPECIFIC_DATETIME_START]' => $datetime->get_i18n_datetime('DTT_EVT_start'),
            '[SPECIFIC_DATETIME_END]' => $datetime->get_i18n_datetime('DTT_EVT_end'),
            '[SPECIFIC_DATETIME_TIMEZONE]' => $datetime->get_timezone(),
            '[SPECIFIC_DATE_START]' => $datetime->get_i18n_datetime('DTT_EVT_start', get_option('date_format')),
            '[SPECIFIC_DATE_END]' => $datetime->get_i18n_datetime('DTT_EVT_end', get_option('date_format')),
            '[SPECIFIC_TIME_START]' => $datetime->get_i18n_datetime('DTT_EVT_start', get_option('time_format')),
            '[SPECIFIC_TIME_END]' => $datetime->get_i18n_datetime('DTT_EVT_end', get_option('time_format'))
        );
        $provided_data = array();
        foreach ($shortcodes_and_expected_result as $shortcode => $expected) {
            $provided_data[] = array(
               array($datetime, array($registration)),
               $shortcode,
               $expected
            );
        }
        return $provided_data;
    }


    /**
     * @group specific_datetimes_shortcode_library
     */
    public function testParser()
    {
        $data_for_testing = $this->getDataForTests();
        //required for php 5.3 compatibility
        $shortcode_library = $this->shortcode_library_mock;
        $test_case = $this;
        array_walk($data_for_testing, function ($test_data) use ($shortcode_library, $test_case) {
            list($data_to_test_with, $shortcode_tested, $expected) = $test_data;
            $fail_message = sprintf('For the shortcode: %s', $shortcode_tested);
            //first test expected empty string
            $test_case->assertEquals(
                '',
                $shortcode_library->parserMockWithoutData($shortcode_tested),
                $fail_message
            );

            //Now test with our data
            $test_case->assertEquals(
                $expected,
                $shortcode_library->parserMockWithData($shortcode_tested, $data_to_test_with),
                $fail_message
            );
        });
    }
}