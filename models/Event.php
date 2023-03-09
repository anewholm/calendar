<?php namespace AcornAssociated\Calendar\Models;

use Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use \AcornAssociated\Location\Models\Location;
use \AcornAssociated\Calendar\Models\Type;
use \AcornAssociated\Calendar\Models\Instance;

/**
 * Model
 */
class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;

    public $table = 'acornassociated_calendar_event';

    protected $nullable = [
        'owner_user_group',
    ];

    public $rules = [
    ];

    public $fillable = [
        'created_at',
        'updated_at',
        // Relations
        'calendar',
        'owner_user',
        'owner_user_group',
        'permissions',
    ];

    public $belongsTo = [
        'calendar' => Calendar::class,
        'owner_user' => User::class,
        'owner_user_group' => UserGroup::class,
    ];

    public $jsonable = [];

    public $guarded = [];
}
