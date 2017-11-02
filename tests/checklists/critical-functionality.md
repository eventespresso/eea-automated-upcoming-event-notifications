This is the checklist for full coverage of critical functionality for the add-on.

### Add-on activation

* [ ] On activation all contexts for the new message types should be disabled and default templates available in the Message Template List table.  **Note:** This expected behaviour is only for fresh installs and activation by the add-on.
* [ ] Using a plugin such as `wp-crontrol` you should see one scheduled event in the list labelled `AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check` and it should be set to run every three hours beginning at midnight.
* [ ] When editing the templates for the new message types, they should have a new metabox for scheduling that has one setting: Setting threshold for sending.
* [ ] ONLY the templates for the new message types should have this new meta-box.

### Sending behaviour.

To test, you want to setup registrations and events and date-times that would meet the conditions for the automation you are testing.  Then you can use a plugin like wp-crontrol to trigger the schedule and see what happens.

The following are defaults on activation of the plugin.

* [ ] Scheduling threshold setting is PER context.
* [ ] When context is set to disabled for a global message template, then no messages should get sent for that message type and context _unless_ there is a custom template and context for that message type that is marked active and assigned to an event.
* [ ] When context is enabled for a global message template, then ALL upcoming events/datetimes for the two message types could have messages sent UNLESS there are any custom templates for one of the message types and context that is marked inactive.  In which case any events assigned to those custom message templates and context should NOT receive any messages.
* [ ] When an event is assigned to a custom template for one of the new message types, then the settings on that message template override any other settings for that message type and that event.

### Expectations for all message types

* [ ] "Already notified" is per event or per datetime.  That means that whenever messages have been triggered for an event or a datetime (depending on the message type), then the registrations/admins matching the criteria at that trigger time will receive notifications.  However, after that point even if there are additional registrations that come in there will be _no further notifications_.  When testing the following behaviour verify keep this expectation in mind.  "Has been notified" is tracked per context.  So that still allows for separate threshold times to be set per context.

### Automated Upcoming Event Notification message type

* [ ] When the cron fires, for the `attendee` context it looks for any _approved_ registrations that for an event that has not already received notifications for _this message type_ where the earliest datetime falls within the set threshold for the message type template attached to that event.  For the `admin` context it looks for any _approved_ registrations belonging to events for which the event has not already been notified for admin. 
* [ ] Recipients are attendees attached to those registrations, and there should only be ONE email per attendee for that event (that may list all the data related to the registrations attached to that attendee).  Note, this also means that the if the attendee has registrations across _multiple_ events that were triggered, then they will see all event registration info listed in the same email for any events attached to that message template group.
* [ ] If the admin context is active, the recipient (whoever is listed in the "to" field) will receive one email containing all the data related to events authored (event admin context = grouped by Event Author) that match the trigger conditions and for the events attached to the message template group. For instance, if there is a custom message template that is attached to only one event and two other events attached to the global event that have datetimes matching the trigger condition for the set threshold in each message template, and each message template group has the same threshold, and all three events have the same author, then there will be two 'admin' context emails sent out, one for the custom template with the single events data, and one for the other two events containing the data for those other two events. By default `[ATTENDEE_LIST]` shortcode is NOT included in the default admin context for this template because there is potential for there to be a large number of registrations matching the threshold and this could cause any messages to fail in being generated when the trigger is executed.

### Automated Upcoming Datetime message type

* [ ] When the cron fires, for the `attendee` context it looks for any _approved_ registrations for a datetime that has not already received notifications for _this message type_ were the datetime falls within the threshold setting for the message template group attached to the event the datetime belongs to.  For the `admin` context it looks for any _approved_ registrations belonging to datetimes for which the datetime has not already been notified for the admin context.
* [ ] Recipients are attendees attached to those registrations, and there should only be ONE email per attendee for each datetime matching the threshold (but multiple registrations might be listed in the email).  However it _is_ expected that there could be _multiple_ emails for each attendee for the event if there are multiple datetimes in that event that match the conditions for the threshold.
* [ ] If the admin context is active, the recipient (whoever is listed in the "to" field) will receive one email for each matching datetime that belongs to an event that is authored by the same user (messages are grouped by event author).  This means that the admin context recipient could receive multiple emails per event if there are more than one datetime in that event matching the threshold conditions.

### Template content

* [ ] When doing tests of messages getting sent, does the content of the templates match what you'd expect?
* [ ] There are new shortcodes introduced for the Automated Upcoming Datetime message type (`[SPECIFIC_DATETIME_`), do those shortcodes parse as expected?