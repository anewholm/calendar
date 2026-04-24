<?php namespace Acorn\Calendar;

use Schema;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use Illuminate\Support\Facades\Event;
use \Acorn\Calendar\Listeners\MixinEvents;
use \Acorn\Calendar\Models\Calendar;
use \Acorn\Calendar\Models\Instance;
use \Acorn\Calendar\Models\EventPart;

class Plugin extends PluginBase
{
    public $require = [];

    public function boot()
    {
        $pm = PluginManager::instance();

        // Optional: Acorn.Messaging plugin integration
        if ($pm->hasPlugin('Acorn.Messaging')) {
            if (class_exists(\Acorn\Messaging\Events\MessageListReady::class))
                Event::listen(
                    \Acorn\Messaging\Events\MessageListReady::class,
                    [MixinEvents::class, 'handle']
                );

            if (class_exists(\Acorn\Messaging\Models\Message::class)) {
                \Acorn\Messaging\Models\Message::extend(function ($model){
                    $model->belongsToMany['instances'] = [
                        Instance::class,
                        'table' => 'acorn_messaging_message_instance',
                        'order' => 'acorn_messaging_message_instance.created_at',
                    ];
                    $model->fillable[] = 'instances';
                });

                \Acorn\Messaging\Controllers\Conversations::extendFormFields(function ($form, $model, $context) {
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

        // Cross-plugin migration: CreateAcornUsersExtraFields adds Calendar columns to the
        // User table.  If Calendar was installed before User, that migration ran but returned
        // early (User tables absent) and was recorded as done by VersionManager — it will
        // never re-run automatically.
        //
        // Two complementary paths ensure it always completes:
        //   1. Event listener — Acorn.User v4.0.1 fires 'acorn.user.tables.ready' from its
        //      migration, so the listener below fires exactly once when User first installs
        //      or upgrades, at which point User tables are guaranteed to exist.
        //   2. Direct call — if User is already installed when Calendar boots (the normal
        //      "User installed first" case), run up() directly; idempotent column-existence
        //      guards inside make repeated calls safe.
        Event::listen('acorn.user.tables.ready', function() {
            require_once __DIR__ . '/updates/create_acorn_users_extra_fields.php';
            (new \Acorn\Calendar\Updates\CreateAcornUsersExtraFields())->up();
        });

        // Optional: Acorn.User plugin integration
        if ($pm->hasPlugin('Acorn.User')) {
            require_once __DIR__ . '/updates/create_acorn_users_extra_fields.php';
            (new \Acorn\Calendar\Updates\CreateAcornUsersExtraFields())->up();

            \Acorn\User\Models\User::extend(function ($model){
                $model->belongsToMany['eventParts'] = [
                    EventPart::class,
                    'table' => 'acorn_calendar_event_part_user',
                ];
            });

            \Acorn\User\Models\UserGroup::extend(function ($model){
                $model->belongsToMany['eventParts'] = [
                    EventPart::class,
                    'table' => 'acorn_calendar_event_part_user_group',
                ];
            });

            \Acorn\User\Controllers\Users::extendFormFields(function ($form, $model, $context) {
                if ($model instanceof \Acorn\User\Models\User) {
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
                }
            });
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
