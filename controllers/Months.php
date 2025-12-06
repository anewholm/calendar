<?php namespace Acorn\Calendar\Controllers;

use Acorn\Controller;
use BackendMenu;

class Months extends Controller
{
    // Custom AA Behavior
    public $implement = [
        'Acorn\Calendar\Behaviors\CalendarController',  
        // Acorn behaviour += MorphConfig for settings
        'Acorn\Behaviors\FormController', 
        'Acorn\Behaviors\ReorderController',
        // groups, users relationmanagers (setting)
        'Acorn\Behaviors\RelationController', 
    ];

    public $implementReplaces = ['Backend\Behaviors\RelationController'];

    public $monthConfig = 'config_month.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Acorn.Calendar', 'calendar-menu-item', 'calendar-month-side-menu-item');

        // Catch popup event description re-directed richeditor uploads
        $this->widget->formDescription = new \Backend\FormWidgets\RichEditor($this, (object)array(
            'fieldName' => 'description',
            'valueFrom' => 'name',
            'disabled'  => false
        ));
    }
}
