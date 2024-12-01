<?php namespace Acorn\Calendar\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Months extends Controller
{
    // Custom AA Behavior
    public $implement = ['Acorn\Calendar\Behaviors\CalendarController',  'Backend\Behaviors\FormController', 'Backend\Behaviors\ReorderController' ];

    public $monthConfig = 'config_month.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Acorn.Calendar', 'calendar-menu-item', 'week-side-menu-item');
    }
}
