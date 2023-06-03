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
use AcornAssociated\Calendar\Events\EventDeleted;

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

    public function delete()
    {
        $result = parent::delete();
        EventDeleted::dispatch($this);
        return $result;
    }

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

    public static function intervalToPeriod($interval)
    {
        $period = NULL;
        if ($interval == '00:00:00') {
            $period = 'PT0M';
        } else if (preg_match('/^00:(\d\d):00$/', $interval, $a)) {
            $x      = $a[1]; 
            $period = "PT${x}M";
        } else if (preg_match('/^(\d\d):00:00$/', $interval, $a)) {
            $x      = $a[1]; 
            $period = "PT${x}H";
        } else if (preg_match('/^(\d) days?$/', $interval, $a)) {
            $x      = $a[1]; 
            $period = "P${x}D";
        }
        return $period;
    }
}
