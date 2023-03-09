<?php namespace AcornAssociated\Calendar\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Calendar extends Controller
{
    // Custom AA Behavior
    public $implement = ['AcornAssociated\Calendar\Behaviors\CalendarController',  'Backend\Behaviors\FormController', 'Backend\Behaviors\ReorderController' ];

    public $calendarConfig = 'config_calendar.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('AcornAssociated.Calendar', 'calendar-menu-item', 'week-side-menu-item');
    }
}
