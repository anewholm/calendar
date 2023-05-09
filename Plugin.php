<?php namespace AcornAssociated\Calendar;

use Schema;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\Event;
use \AcornAssociated\Calendar\Listeners\MixinEvents;
use \AcornAssociated\Messaging\Events\MessageListReady;
use Backend\Models\User;
use Backend\Controllers\Users;
use \AcornAssociated\Calendar\Models\Calendar;

class Plugin extends PluginBase
{
    public $require = ['AcornAssociated.Location'];

    public function boot()
    {
        // Listen to Messaging plugin events
        if (class_exists('MessageListReady'))
            Event::listen(
                MessageListReady::class,
                [MixinEvents::class, 'handle']
            );

        // We need to be careful when using the database
        // during migrations, tables may not exist
        $calendars = array();
        if (Schema::hasTable('acornassociated_calendar')) $calendars = Calendar::all();
        $calendarOptions = array();
        foreach ($calendars as $calendar) $calendarOptions[$calendar->id] = $calendar->name;

        Users::extendFormFields(function ($form, $model, $context) use ($calendarOptions) {
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
