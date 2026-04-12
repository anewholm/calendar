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
        $this->bodyClass = 'compact-container';
        parent::__construct();
        BackendMenu::setContext('Acorn.Calendar', 'calendar-menu-item', 'calendars-side-menu-item');
    }

    public function formExtendFields($form, $fields = [])
    {
        // owner_user and owner_user_group are optional — require Acorn.User plugin.
        // Added here rather than in fields.yaml so WinterCMS 1.2.0 does not attempt
        // to create relation widgets for non-existent model classes.
        if (class_exists(\Acorn\User\Models\User::class)) {
            $form->addFields([
                'owner_user' => [
                    'label'           => 'acorn.calendar::lang.models.general.owner_user',
                    'nameFrom'        => 'name',
                    'descriptionFrom' => 'description',
                    'placeholder'     => 'backend::lang.form.select_none',
                    'span'            => 'left',
                    'type'            => 'relation',
                ],
                'owner_user_group' => [
                    'label'           => 'acorn.calendar::lang.models.general.owner_group',
                    'nameFrom'        => 'name',
                    'descriptionFrom' => 'description',
                    'placeholder'     => 'backend::lang.form.select_none',
                    'span'            => 'left',
                    'type'            => 'relation',
                ],
            ]);
        }
    }
}
