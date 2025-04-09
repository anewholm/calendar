<?php namespace AcornAssociated\Calendar\Controllers;

use AcornAssociated\Controller;
use BackendMenu;

class Months extends Controller
{
    // Custom AA Behavior
    public $implement = ['AcornAssociated\Calendar\Behaviors\CalendarController',  'AcornAssociated\Behaviors\FormController', 'AcornAssociated\Behaviors\ReorderController' ];

    public $monthConfig = 'config_month.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('AcornAssociated.Calendar', 'calendar-menu-item', 'week-side-menu-item');
    }
}
