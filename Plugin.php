<?php namespace AcornAssociated\Calendar;

use System\Classes\PluginBase;
use Illuminate\Support\Facades\Event;
use \AcornAssociated\Calendar\Listeners\MixinEvents;
use \AcornAssociated\Messaging\Events\MessageListReady;

class Plugin extends PluginBase
{
    public $require = ['AcornAssociated.Location'];

    public function boot()
    {
        Event::listen(
            MessageListReady::class,
            [MixinEvents::class, 'handle']
        );
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
