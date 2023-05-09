<?php namespace AcornAssociated\Calendar\Models;

use Model;
use BackendAuth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use \AcornAssociated\Location\Models\Location;
use \AcornAssociated\Calendar\Models\Type;
use \AcornAssociated\Calendar\Models\Instance;
use \Illuminate\Auth\Access\AuthorizationException;

/**
 * Model
 */
class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use \AcornAssociated\LinuxPermissions;

    public $table = 'acornassociated_calendar_event';

    protected $nullable = [
        'owner_user_group_id',
    ];

    public $rules = [
        'owner_user' => 'required',
        'calendar'   => 'required',
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
        'calendar'   => Calendar::class,
        'owner_user' => User::class,
        'owner_user_group' => UserGroup::class,
    ];

    public $hasMany = [
        'event_parts' => [
            EventPart::class,
            'table' => 'acornassociated_calendar_event_part',
            'order' => 'start',
        ],
    ];

    public $jsonable = ['permissions'];

    public $guarded = [];

    public static function canPast(\DateTime $date)
    {
        $user             = BackendAuth::user();
        $isPast           = ($date < new \DateTime());
        $canChangeThePast = $user->hasAccess('acornassociated.calendar.change_the_past');

        return ($user->is_superuser || !$isPast || $canChangeThePast);
    }

    public function permissions(): Attribute
    {
        // TODO: Why does this not work in the trait?
        return Attribute::make(
            set: fn ($value) => array_sum(json_decode($value)),
        );
    }
}
