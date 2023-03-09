<?php namespace AcornAssociated\Calendar;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $require = ['AcornAssociated.Location'];

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
