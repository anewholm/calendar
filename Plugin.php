<?php namespace Acorn\Calendar;

use Schema;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\Event;
use \Acorn\Calendar\Listeners\MixinEvents;
use \Acorn\Messaging\Events\MessageListReady;
use Acorn\User\Models\User;
use Acorn\User\Models\UserGroup;
use Acorn\User\Controllers\Users;
use \Acorn\Messaging\Controllers\Conversations;
use \Acorn\Messaging\Models\Message;
use \Acorn\Calendar\Models\Calendar;
use \Acorn\Calendar\Models\Instance;
use \Acorn\Calendar\Models\EventPart;
use \Acorn\Events\ModelBeforeSave;
use \Acorn\Events\ModelAfterSave;
use \Acorn\Calendar\Listeners\CompleteCreatedAtEvent;
use \Acorn\Calendar\Listeners\CompleteCreatedAtEventExternalUrl;

class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = ['Acorn.Location', 'Acorn.Messaging', 'Acorn.User'];

    public function boot()
    {
        // Listen to Messaging plugin events
        // TODO: Test uninstall Messaging
        if (class_exists(MessageListReady::class))
            Event::listen(
                MessageListReady::class,
                [MixinEvents::class, 'handle']
            );

        // Fill out created_at_event_id fields
        Event::listen(
            ModelBeforeSave::class,
            [CompleteCreatedAtEvent::class, 'handle']
        );
        // Complete external_url
        Event::listen(
            ModelAfterSave::class,
            [CompleteCreatedAtEventExternalUrl::class, 'handle']
        );

        User::extend(function ($model){
            $model->belongsToMany['eventParts'] = [
                EventPart::class,
                'table' => 'acorn_calendar_event_part_user',
            ];
        });

        UserGroup::extend(function ($model){
            $model->belongsToMany['eventParts'] = [
                EventPart::class,
                'table' => 'acorn_calendar_event_part_user_group',
            ];
        });

        Users::extendFormFields(function ($form, $model, $context) {
            // We need to be careful when using the database
            // during migrations, tables may not exist
            $calendars = array();
            if (Schema::hasTable('acorn_calendar_calendars')) $calendars = Calendar::all();
            $calendarOptions = array();
            foreach ($calendars as $calendar) $calendarOptions[$calendar->id] = $calendar->name;

            $form->addTabFields([
                'acorn_default_calendar' => [
                    'label'   => 'acorn.calendar::lang.models.settings.default_calendar',
                    'tab'     => 'acorn.calendar::lang.models.calendar.label',
                    'span'    => 'left',
                    'type'    => 'dropdown',
                    'options' => $calendarOptions,
                ],
                'acorn_start_of_week' => [
                    'label' => 'acorn.calendar::lang.models.settings.start_of_the_week',
                    'tab'   => 'acorn.calendar::lang.models.calendar.label',
                    'span'  => 'right',
                    'type'  => 'dropdown',
                    'options' => [
                        1 => trans('acorn.calendar::lang.models.calendar.weekdays.1'),
                        2 => trans('acorn.calendar::lang.models.calendar.weekdays.2'),
                        3 => trans('acorn.calendar::lang.models.calendar.weekdays.3'),
                        4 => trans('acorn.calendar::lang.models.calendar.weekdays.4'),
                        5 => trans('acorn.calendar::lang.models.calendar.weekdays.5'),
                        6 => trans('acorn.calendar::lang.models.calendar.weekdays.6'),
                        7 => trans('acorn.calendar::lang.models.calendar.weekdays.0'),
                    ],
                ],
                'acorn_default_event_time_from' => [
                    'label'   => 'acorn.calendar::lang.models.settings.default_event_time_from',
                    'tab'     => 'acorn.calendar::lang.models.calendar.label',
                    'span'    => 'left',
                    'type'    => 'datepicker',
                    'mode'    => 'time',
                    'format'  => 'H:i',
                    'default' => '09:00',
                ],
                'acorn_default_event_time_to' => [
                    'label'   => 'acorn.calendar::lang.models.settings.default_event_time_to',
                    'tab'     => 'acorn.calendar::lang.models.calendar.label',
                    'span'    => 'right',
                    'type'    => 'datepicker',
                    'mode'    => 'time',
                    'format'  => 'H:i',
                    'default' => '10:00',
                ],
            ]);
        });

        if (class_exists(Message::class)) {
            Message::extend(function ($model){
                $model->belongsToMany['instances'] = [
                    Instance::class,
                    'table' => 'acorn_messaging_message_instance',
                    'order' => 'acorn_messaging_message_instance.created_at',
                ];
                $model->fillable[] = 'instances';
            });

            Conversations::extendFormFields(function ($form, $model, $context) {
                $docroot   = app()->basePath();
                $pluginDir = str_replace($docroot, '~', dirname(__FILE__));
                $form->addTabFields([
                    'instances' => [
                        'label'   => '',
                        'tab'     => 'acorn.calendar::lang.models.calendar.label',
                        'span'    => 'left',
                        'type'    => 'partial',
                        'comment' => trans('acorn.messaging::lang.models.calendar.select_events'),
                        'path'    => "$pluginDir/widgets/calendars/partials/_calendar_selector",
                        'options' => array(
                            'period' => 'week',
                        ),
                    ],
                ]);
            });

            /* TODO: Add messaging list to the events
            Calendar::extendFormFields(function ($form, $model, $context) {
                $form->addTabFields([
                    'messages' => [
                        'label'   => '',
                        'tab'     => 'Discussion',
                        'type'    => 'text', //'partial',
                        'comment' => trans('acorn.messaging::lang.models.calendar.select_events'),
                        'path'    => "messages",
                        'options' => array(
                        ),
                    ],
                ]);
            });
            */
        }
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'acorn.calendar::lang.models.settings.label_plural',
                'description' => 'acorn.calendar::lang.models.settings.description',
                'category'    => 'Acorn',
                'icon'        => 'icon-calendar',
                'class'       => 'Acorn\Calendar\Models\Settings',
                'order'       => 500,
                'keywords'    => 'calendar event meeting',
                'permissions' => ['acorn.calendar.settings']
            ]
        ];
    }
}
