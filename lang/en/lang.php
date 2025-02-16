<?php return [
    'plugin' => [
        'name' => 'Calendar',
        'description' => 'Time visualization'
    ],
    'permissions' => [
        'view_the_calendar' => 'View the Calendar',
        'change_the_past' => 'Change the past',
        'access_settings' => 'Access settings'
    ],
    'models' => [
        'general' => [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'owner_user' => 'Owner',
            'owner_group' => 'Owner Group',
            'permissions' => 'Permissions',
            'location' => 'Location',
            'locations' => 'Locations',
            'groups' => 'Groups',
            'users' => 'Users',
            'security' => [
                'read' => 'Read',
                'write' => 'Write',
                'delete' => 'Delete',
                'user' => 'User',
                'group' => 'Group',
                'other' => 'Other'
            ],
            'system' => 'System'
        ],
        'calendar' => [
            'label' => 'Calendar',
            'label_plural' => 'Calendars',
            'manage' => 'Manage',
            'day_add_event' => '+event',
            'calendars' => 'Calendars',
            'month' => 'Month',
            'calendar' => 'Calendar',
            'date_range' => 'Date Range',
            'status' => 'Status',
            'type' => 'Type',
            'my_events' => 'My Events',
            'attending' => 'Attending',
            'sync_file' => 'Sync File',
            'sync_format' => 'Sync Format',
            'display' => 'Display',
            'previousMonth' => 'Previous Month',
            'nextMonth' => 'Next Month',
            'months' => [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December'
            ],
            'weekdays' => [
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday'
            ],
            'weekdaysShort' => [
                'Sun',
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
                'Sat'
            ]
        ],
        'event' => [
            'label' => 'Event',
            'label_plural' => 'Events'
        ],
        'eventpart' => [
            'label' => 'Event Part',
            'label_plural' => 'Event Parts',
            'mask' => 'Mask',
            'part' => 'Part',
            'time' => 'Time',
            'type' => 'Type',
            'status' => 'Status',
            'start' => 'Start',
            'end' => 'End',
            'reminder' => 'Reminder',
            'groups' => 'Groups',
            'repeat' => 'Repeat',
            'days' => 'Days',
            'frequency' => 'Frequency',
            'repeat_frequency' => 'Repeat Frequency',
            'mask_type' => 'Mask Type',
            'container_event' => 'Container Event',
            'until' => 'Until',
            'attendees' => 'Attendees',
            'place' => 'Place',
            'content' => 'Notes',
            'repetition' => 'Repetition',
            'attributes' => 'Attributes',
            'people' => 'People',
            'security' => 'Security',
            'repeat_type' => [
                'none' => 'None',
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'yearly' => 'Yearly'
            ],
            'alarm_type' => [
                'none' => 'None',
                'at_the_event_time' => 'At the event time',
                '5_minutes' => '5 minutes',
                '10_minutes' => '10 minutes',
                '15_minutes' => '15 minutes',
                '30_minutes' => '30 minutes',
                '1_hour' => '1 hour',
                '2_hours' => '2 hours',
                '5_hours' => '5 hours',
                '12_hours' => '12 hours',
                '1_day' => '1 day',
                '2_days' => '2 days'
            ]
        ],
        'instance' => [
            'label' => 'Event Instance',
            'label_plural' => 'Event Instances',
            'date' => 'Date',
            'instance_num' => 'Instance Num',
            'instance_start' => 'Instance Start',
            'instance_end' => 'Instance End',
            'repeat' => 'Repeat',
            'attendees' => 'Attendees',
            'writeable' => 'Writeable',
            'locked' => 'Locked',
            'reminder' => 'Reminder'
        ],
        'settings' => [
            'label' => 'Calendar Setting',
            'label_plural' => 'Calendar Settings',
            'label_short_plural' => 'Settings',
            'description' => 'Manage calendar based settings',
            'event_window_before' => 'Event Window Before',
            'event_window_after' => 'Event Window After',
            'default_calendar' => 'Default Calendar',
            'start_of_the_week' => 'Start of the week',
            'default_event_time_from' => 'Default Event Time From',
            'default_event_time_to' => 'Default Event Time To',
            'default_time_zone' => 'Default Time Zone',
            'daylight_savings' => 'Daylight Savings',
            'event_window_before_comment' => 'Period in years for event repetition to be pre-calculated',
            'event_window_after_comment' => 'Period in years for event repetition to be pre-calculated',
            'default_time_zone_comment' => 'This will also be the default TZ output for sync files',
            'years' => [
                '1year' => '1 year',
                '1year_default' => '1 year (default)',
                '2years' => '2 years',
                '2years_default' => '2 years (default)',
                '3years' => '3 years',
                '4years' => '4 years',
                '5years' => '5 years',
                '6years' => '6 years',
                '7years' => '7 years',
                '8years' => '8 years',
                '9years' => '9 years',
                '10years' => '10 years'
            ]
        ],
        'eventstatus' => [
            'label' => 'Event Status',
            'label_plural' => 'Event Statuses',
            'style' => 'Style'
        ],
        'eventtype' => [
            'label' => 'Event Type',
            'label_plural' => 'Event Types',
            'color' => 'Colour',
            'style' => 'Style',
            'whole_day' => 'Whole Day'
        ]
    ]
];