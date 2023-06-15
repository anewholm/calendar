<?php namespace AcornAssociated\Calendar;

use Schema;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\Event;
use \AcornAssociated\Calendar\Listeners\MixinEvents;
use \AcornAssociated\Messaging\Events\MessageListReady;
use Backend\Models\User;
use Backend\Models\UserGroup;
use Backend\Controllers\Users;
use \AcornAssociated\Messaging\Controllers\Conversations;
use \AcornAssociated\Messaging\Models\Message;
use \AcornAssociated\Calendar\Models\Calendar;
use \AcornAssociated\Calendar\Models\Instance;
use \AcornAssociated\Calendar\Models\EventPart;

class Plugin extends PluginBase
{
    public $require = ['AcornAssociated.Location'];

    public function boot()
    {
        // Listen to Messaging plugin events
        // TODO: Test uninstall Messaging
        if (class_exists(MessageListReady::class))
            Event::listen(
                MessageListReady::class,
                [MixinEvents::class, 'handle']
            );
        
        User::extend(function ($model){
            $model->belongsToMany['eventParts'] = [
                EventPart::class,
                'table' => 'acornassociated_calendar_event_user',
            ];
        });

        UserGroup::extend(function ($model){
            $model->belongsToMany['eventParts'] = [
                EventPart::class,
                'table' => 'acornassociated_calendar_event_user_group',
            ];
        });

        Users::extendFormFields(function ($form, $model, $context) {
            // We need to be careful when using the database
            // during migrations, tables may not exist
            $calendars = array();
            if (Schema::hasTable('acornassociated_calendar')) $calendars = Calendar::all();
            $calendarOptions = array();
            foreach ($calendars as $calendar) $calendarOptions[$calendar->id] = $calendar->name;

            $form->addTabFields([
                'acornassociated_default_calendar' => [
                    'label'   => 'Default Calendar',
                    'tab'     => 'Calendar',
                    'span'    => 'left',
                    'type'    => 'dropdown',
                    'options' => $calendarOptions,
                ],
                'acornassociated_start_of_week' => [
                    'label' => 'Start of the week',
                    'tab'   => 'Calendar',
                    'span'  => 'right',
                    'type'  => 'dropdown',
                    'options' => [
                        1 => trans('Monday'),
                        2 => trans('Tuesday'),
                        3 => trans('Wednesday'),
                        4 => trans('Thursday'),
                        5 => trans('Friday'),
                        6 => trans('Saturday'),
                        7 => trans('Sunday'),
                    ],
                ],
                'acornassociated_default_event_time_from' => [
                    'label'   => 'Default Event start',
                    'tab'     => 'Calendar',
                    'span'    => 'left',
                    'type'    => 'datepicker',
                    'mode'    => 'time',
                    'format'  => 'H:i',
                    'default' => '09:00',
                ],
                'acornassociated_default_event_time_to' => [
                    'label'   => 'Default Event end',
                    'tab'     => 'Calendar',
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
                    'table' => 'acornassociated_messaging_message_instance',
                    'order' => 'id',
                ];
                $model->fillable[] = 'instances';
            });

            Conversations::extendFormFields(function ($form, $model, $context) {
                $docroot   = app()->basePath();
                $pluginDir = str_replace($docroot, '~', dirname(__FILE__));
                $form->addTabFields([
                    'instances' => [
                        'label'   => '',
                        'tab'     => 'Calendar',
                        'span'    => 'left',
                        'type'    => 'partial',
                        'comment' => trans('Select the events that concern this message'),
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
                        'comment' => trans('Select the events that concern this message'),
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
                'label'       => 'Calendar Settings',
                'description' => 'Manage calendar based settings.',
                'category'    => 'Calendars',
                'icon'        => 'icon-cog',
                'class'       => 'AcornAssociated\Calendar\Models\Settings',
                'order'       => 500,
                'keywords'    => 'calendar event meeting',
                'permissions' => []
            ]
        ];
    }
}
