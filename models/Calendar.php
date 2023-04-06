<?php namespace AcornAssociated\Calendar\Models;

use Model;
use \Backend\Models\User;
use \Backend\Models\UserGroup;

/**
 * Model
 */
class Calendar extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;

    public $table = 'acornassociated_calendar';

    protected $nullable = [
        'owner_user_group',
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [
        'owner_user' => User::class,
        'owner_user_group' => UserGroup::class,
    ];

    public $hasMany = [
        'events' => [
            Event::class,
            'table' => 'acornassociated_calendar_event',
        ],
    ];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [];
}
