<?php namespace Acorn\Calendar\Models;

use Model;
use BackendAuth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use \Acorn\Location\Models\Location;
use \Acorn\Calendar\Models\Type;
use \Acorn\Calendar\Models\Instance;
use \Illuminate\Auth\Access\AuthorizationException;
use Acorn\Calendar\Events\EventDeleted;

class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use \Acorn\Traits\LinuxPermissions;

    public $table = 'acorn_calendar_event';

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
            'table' => 'acorn_calendar_event_part',
            'order' => 'start',
        ],
    ];

    public $jsonable = ['permissions'];

    public $guarded = [];

    public function delete()
    {
        EventDeleted::dispatch($this);
        return parent::delete();
    }

    public static function canPast(\DateTime $date)
    {
        $user             = BackendAuth::user();
        $isPast           = ($date < new \DateTime());
        $canChangeThePast = $user->hasAccess('acorn.calendar.change_the_past');

        return ($user->is_superuser || !$isPast || $canChangeThePast);
    }

    protected function permissions(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => array_sum(json_decode($value)),
        );
    }

    /*
     * Conversion
     */
    public static function naturalInterval(?string $interval)
    {
        // PostgreSQL understands, but changes interval strings
        // e.g. 1 week => 7 days
        $natural = $interval;

        if ($interval == '00:00:00') {
            $natural = '00:00:00';
        } else if ($interval == '7 days') {
            $natural = '1 week';
        } else if (preg_match('/^(\d+) mons?$/', $interval, $a)) {
            $x      = (int) $a[1]; 
            $plural = ($x > 1 ? 's' : '');
            $natural = "$x month$plural";
        } else if (preg_match('/^00:(\d\d):00$/', $interval, $a)) {
            $x      = (int) $a[1]; 
            $plural = ($x > 1 ? 's' : '');
            $natural = "$x minute$plural";
        } else if (preg_match('/^(\d\d):00:00$/', $interval, $a)) {
            $x      = (int) $a[1]; 
            $plural = ($x > 1 ? 's' : '');
            $natural = "$x hour$plural";
        }

        return $natural;
    }

    public static function intervalToPeriod(?string $interval)
    {
        // PostGRESQL interval => PHP DateTimeInterval string
        // e.g. 00:30:00 => PT30M
        // Useful for iCalendar format
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
