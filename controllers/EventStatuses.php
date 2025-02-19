<?php namespace AcornAssociated\Calendar\Controllers;

use AcornAssociated\Controller;
use BackendMenu;

class EventStatuses extends Controller
{
    public $implement = [        'AcornAssociated\Behaviors\ListController',        'AcornAssociated\Behaviors\FormController',        'AcornAssociated\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('AcornAssociated.Calendar', 'calendar-menu-item', 'status-side-menu-item');
    }
}
