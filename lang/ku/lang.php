<?php return [
    'plugin' => [
        'name' => 'Salname',
        'description' => 'Dem nîşan dide'
    ],
    'permissions' => [
        'view_the_calendar' => 'Salnameyê bibînin',
        'change_the_past' => 'Rabirdûyê biguherînin',
        'access_settings' => 'Mîhengên Gihîştinê'
    ],
    'models' => [
        'general' => [
            'id' => 'ID',
            'name' => 'Nav',
            'description' => 'Terîf',
            'created_at' => 'Dema afirandin',
            'updated_at' => 'Dema guhertin',
            'owner_user' => 'Xwedî',
            'owner_group' => 'Koma Xwedî',
            'permissions' => 'Destûrên',
            'location' => 'Cîh',
            'locations' => 'Cîhên',
            'groups' => 'Komên',
            'users' => 'Kesên',
            'security' => [
                'read' => 'Xwendin',
                'write' => 'Nivîsîn',
                'delete' => 'Jêbirin',
                'user' => 'Kes',
                'group' => 'Kom',
                'other' => 'Din'
            ],
            'system' => 'Sîstem'
        ],
        'calendar' => [
            'label' => 'Salname',
            'label_plural' => 'Salnamen',
            'manage' => 'Birêvebirin',
            'day_add_event' => '+bûyer',
            'calendars' => 'Salnamen',
            'month' => 'Meh',
            'calendar' => 'Salname',
            'date_range' => 'Rêza Dîrokê',
            'status' => 'Rewş',
            'type' => 'Tîp',
            'my_events' => 'Bûyerên Min',
            'attending' => 'Beşdarbûn',
            'sync_file' => 'Pelê hevdemkirinê',
            'sync_format' => 'Forma hevdengkirinê',
            'display' => 'Nişandan',
            'previousMonth' => 'Meha Berê',
            'nextMonth' => 'Meha pêş',
            'months' => [
                'Rêbendan',
                'Reşemî',
                'Adar',
                'Avrêl',
                'Gulan',
                'Pûşper',
                'Tîrmeh',
                'Tebax',
                'Îlon',
                'Cotmeh',
                'Mijdar',
                'Berfanbar'
            ],
            'weekdays' => [
                'Yekşem',
                'Duşem',
                'Sêşem',
                'Çarşem',
                'Pêncşem',
                'Roja Înê',
                'Şemî'
            ],
            'weekdaysShort' => [
                'Yek',
                'Du',
                'Sê',
                'Çar',
                'Pênc',
                'Înê',
                'Şem'
            ]
        ],
        'event' => [
            'label' => 'Bûyer',
            'label_plural' => 'Bûyerên'
        ],
        'eventpart' => [
            'label' => 'Beşa Bûyerê',
            'label_plural' => 'Beşên Bûyerê',
            'mask' => 'Mask',
            'part' => 'Beş',
            'time' => 'Dem',
            'type' => 'Tip',
            'status' => 'Rewş',
            'start' => 'Destpêk',
            'end' => 'Dawiya',
            'reminder' => 'Bir Kirin',
            'groups' => 'Grûpên',
            'repeat' => 'Dubare bike',
            'days' => 'Rojan',
            'frequency' => 'Çiqas',
            'repeat_frequency' => 'Dîbara Çiqas',
            'mask_type' => 'Tipa Mask',
            'container_event' => 'Bûyera Konteyner',
            'until' => 'Heta',
            'attendees' => 'Beşdar',
            'place' => 'Cih',
            'content' => 'Têbînî',
            'repetition' => 'Dubarekirin',
            'attributes' => 'Taybetmendî',
            'people' => 'Kesên',
            'security' => 'Ewlekarî',
            'repeat_type' => [
                'none' => 'Nine',
                'daily' => 'Rojane',
                'weekly' => 'Hefteyî',
                'monthly' => 'Mehai',
                'yearly' => 'Salane'
            ],
            'alarm_type' => [
                'none' => 'Nine',
                'at_the_event_time' => 'Di dema bûyerê de',
                '5_minutes' => '5 deqe',
                '10_minutes' => '10 deqe',
                '15_minutes' => '15 deqe',
                '30_minutes' => '30 deqe',
                '1_hour' => '1 saet',
                '2_hours' => '2 saet',
                '5_hours' => '5 saet',
                '12_hours' => '12 saet',
                '1_day' => '1 roj',
                '2_days' => '2 roj'
            ]
        ],
        'instance' => [
            'label' => 'Nimûneya Bûyer',
            'label_plural' => 'Nimûneyan Bûyer',
            'date' => 'Dîrok',
            'instance_num' => 'Nimûneya Numre',
            'instance_start' => 'Nimûne Destpêk',
            'instance_end' => 'Dawiya Mînakê',
            'repeat' => 'Dubare kirin',
            'attendees' => 'Beşdar',
            'writeable' => 'Binivîsîne',
            'locked' => 'Girtî',
            'reminder' => 'Birkirin'
        ],
        'settings' => [
            'label' => 'Mîhengkirina Salnameyê',
            'label_plural' => 'Mîhengên Salnameyê',
            'label_short_plural' => 'Mîheng',
            'description' => 'Mîhengên li ser salnameyê bi rêve bibin',
            'event_window_before' => 'Pencereya Bûyerê Berê',
            'event_window_after' => 'Pencera Bûyerê Piştî',
            'default_calendar' => 'Salnameya xwerû',
            'start_of_the_week' => 'Destpêka hefteyê',
            'default_event_time_from' => 'Dema Bûyerê ya Xweserî Ji',
            'default_event_time_to' => 'Dema Bûyera Pêşniyaz Bo',
            'default_time_zone' => 'Herêma Demjimêra Pêşniyazkirî',
            'daylight_savings' => 'Teserûfa rojê',
            'event_window_before_comment' => 'Dema bi salan ji bo dubarekirina bûyerê ji berê ve were hesibandin',
            'event_window_after_comment' => 'Dema bi salan ji bo dubarekirina bûyerê ji berê ve were hesibandin',
            'default_time_zone_comment' => 'Ev ê di heman demê de ji bo pelên hevdengkirinê derana TZ-ya xwerû be',
            'years' => [
                '1year' => '1 sal',
                '1year_default' => '1 sal (default)',
                '2years' => '2 sal',
                '2years_default' => '2 sal (default)',
                '3years' => '3 sal',
                '4years' => '4 sal',
                '5years' => '5 sal',
                '6years' => '6 sal',
                '7years' => '7 sal',
                '8years' => '8 sal',
                '9years' => '9 sal',
                '10years' => '10 sal'
            ]
        ],
        'eventstatus' => [
            'label' => 'Rewşa Bûyerê',
            'label_plural' => 'Rewşan Bûyerê',
            'style' => 'Şêwe'
        ],
        'eventtype' => [
            'label' => 'Cûreya bûyerê',
            'label_plural' => 'Cûreyan bûyerê',
            'color' => 'Reng',
            'style' => 'Şêwe',
            'whole_day' => 'Hemu roj'
        ]
    ]
];