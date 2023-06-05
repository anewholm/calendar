<?php namespace AcornAssociated\Calendar\Models;

use Model;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Calendar extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use \AcornAssociated\LinuxPermissions;

    public $table = 'acornassociated_calendar';

    protected $nullable = [
        'owner_user_group',
    ];

    public $fillable = [
        'name',
        'description',
        'sync_file',
        'sync_format',
        'created_at',
        'updated_at',
        'permissions',
        // Relations
        'owner_user',
        'owner_user_group',
    ];

    public $guarded = [];

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

    public $jsonable = ['permissions'];

    protected function permissions(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => array_sum(json_decode($value)),
        );
    }
}
