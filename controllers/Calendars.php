<?php namespace Acorn\Calendar\Controllers;

use Acorn\Controller;
use BackendMenu;

class Calendars extends Controller
{
    public $implement = [        'Acorn\Behaviors\ListController',        'Acorn\Behaviors\FormController',        'Acorn\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Acorn.Calendar', 'calendar-menu-item', 'calendars-side-menu-item');
    }
}
