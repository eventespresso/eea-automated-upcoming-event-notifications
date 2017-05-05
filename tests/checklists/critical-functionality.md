This is the checklist for full coverage of critical functionality for the add-on.

### Add-on activation

* [ ] On activation both message types should be active and default templates available in the Message Template List table.  **Note:**  If you've activated this add-on at _any point_ in its development, this _may_ mean the message types are NOT active by default.  This expected behaviour is only for fresh installs and activation by the add-on.
* [ ] Using a plugin such as `wp-crontrol` you should see one scheduled event in the list labelled `AHEE__EventEspresso_AutomatedEventNotifications_core_tasks_Scheduler__daily_check` and it should be set to run daily at midnight.
* [ ] When editing the templates for the new message types, they should have a new metabox for scheduling that has two settings: Setting threshold for sending, and setting whether the automation is active or not.
* [ ] ONLY the templates for the new message types should have this new meta-box.

### Sending behaviour.

To test, you want to setup registrations and events and date-times that would meet the conditions for the automation you are testing.  Then you can use a plugin like wp-crontrol to trigger the schedule and see what happens.

The following are defaults on activation of the plugin.

* [ ] When the automation activated setting is set to inactive for a global message template, then no messages should get sent for that message type _unless_ there is a custom template for that message type that is marked active and assigned to an event.
* [ ] When automation activated setting is set to active for a global message template, then ALL upcoming events/datetimes for the two message types could have messages sent UNLESS there are any custom templates for one of the message types that is marked inactive.  In which case any events assigned to those custom message templates should NOT receive any messages.
* [ ] When an event is assigned to a custom template for one of the new message types, then the settings on that message template override any other settings for that message type and that event.

### Automated Upcoming Event Notification message type

* [ ] When the cron fires, it looks for any _approved_ registrations that have not already been notified for _this message type_  that belong to an event in which the earliest datetime falls within the set threshold for the message type template attached to that event.
* [ ] Recipients are attendees attached to those registrations, and there should only be ONE email per attendee for that event (that may list all the data related to the registrations attached to that attendee).
* [ ] If the admin context is active, the recipient (whoever is listed in the "to" field) will receive ONE email per event matching the conditions that exposes data related to all registrations discovered during that notification trigger.

### Automated Upcoming Datetime message type

* [ ] When the cron fires, it looks for any _approved_ registrations that have not already been notified for specific matching datetimes and _this message type_ for any datetime that matches the threshold setting for the message template group attached to the event the datetime belongs to.
* [ ] Recipients are attendees attached to those registrations, and there should only be ONE email per attendee for each datetime matching the threshold.  However it _is_ expected that there could be _multiple_ emails for each attendee for the event if there are multiple datetimes in that event that match the conditions for the threshold.
* [ ] If the admin context is active, the recipient (whoever is listed in the "to" field) will receive ONE email for each matching datetime.  This means that the admin context recipient could receive multiple emails per event if there are more than one datetimes in that event matching the threshold conditions.

### Template content

* [ ] When doing tests of messages getting sent, does the content of the templates match what you'd expect?
* [ ] There are new shortcodes introduced for the Automated Upcoming Datetime message type (`[SPECIFIC_DATETIME_`), do those shortcodes parse as expected?