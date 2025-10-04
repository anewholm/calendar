<?php namespace Acorn\Calendar\Controllers;

use Acorn\Controller;
use BackendMenu;

class EventTypes extends Controller
{
    public $implement = [        'Acorn\Behaviors\ListController',        'Acorn\Behaviors\FormController',        'Acorn\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        $this->bodyClass = 'compact-container';
        parent::__construct();
        BackendMenu::setContext('Acorn.Calendar', 'calendar-menu-item', 'type-side-menu-item');
    }
}
