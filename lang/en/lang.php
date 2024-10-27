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
            'owner' => 'Owner',
            'owner_group' => 'Owner Group',
            'permissions' => 'Permissions',
            'location' => 'Location',
            'groups' => 'Groups',
            'users' => 'Users'
        ],
        'calendar' => [
            'label' => 'Calendar',
            'label_plural' => 'Calendars',
            'month' => 'Month',
            'date_range' => 'Date Range',
            'status' => 'Status',
            'type' => 'Type',
            'my_events' => 'My Events',
            'attending' => 'Attending',
            'sync_file' => 'Sync File',
            'sync_format' => 'Sync Format'
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
            'content' => 'Content',
            'repetition' => 'Repetition',
            'attributes' => 'Attributes',
            'people' => 'People',
            'security' => 'Security'
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
            'label' => 'Setting',
            'label_plural' => 'Settings',
            'event_window_before' => 'Event Window Before',
            'event_window_after' => 'Event Window After',
            'default_event_time_from' => 'Default Event Time From',
            'default_event_Time_to' => 'Default Event Time To',
            'default_time_zone' => 'Default Time Zone',
            'daylight_savings' => 'Daylight Savings'
        ],
        'status' => [
            'label' => 'Event Status',
            'label_plural' => 'Event Statuses',
            'style' => 'Style'
        ],
        'type' => [
            'label' => 'Event Type',
            'label_plural' => 'Event Types',
            'color' => 'Colour',
            'style' => 'Style',
            'whole_day' => 'Whole Day'
        ]
    ]
];