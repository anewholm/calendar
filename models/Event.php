<?php namespace Acorn\Calendar\Models;

use Carbon\Carbon;
use Acorn\Model;
use Acorn\Collection;
use BackendAuth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Acorn\User\Models\User;
use \Acorn\User\Models\UserGroup;
use \Acorn\Location\Models\Location;
use \Acorn\Calendar\Models\EventType;
use \Acorn\Calendar\Models\Instance;
use \Illuminate\Auth\Access\AuthorizationException;
use Acorn\Calendar\Events\EventDeleted;

class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use \Acorn\Traits\LinuxPermissions;

    public $table = 'acorn_calendar_events';

    protected $nullable = [
        'owner_user_group_id',
    ];

    public $translatable = [
        'create_event_part[name]',
        'create_event_part[description]',
        'first_event_part[name]',
        'first_event_part[description]',
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

    public $hasOne = [
        // Useful for Event forms that only care about 1 event_part
        // Also see the custom save() below that uses a jsonable create_event_part during create
        'first_event_part' => [
            EventPart::class,
            'table' => 'acorn_calendar_event_parts',
            // Limit the query to the first event_part for update actions
            // 'conditions' => 'id = (select id from acorn_calendar_event_parts eps where eps.event_id = event_id order by start limit 1)'
        ]
    ];

    public $hasMany = [
        // Useful when there are multiple eventparts for an event
        'event_parts' => [
            EventPart::class,
            'table' => 'acorn_calendar_event_parts',
            'order' => 'start',
        ],
    ];

    public $jsonable = ['permissions', 'create_event_part'];

    public $guarded = [];

    public function save(?array $options = [], $sessionKey = null)
    {
        // If we have an create_event_part in the post(), then we need to create it manually
        // because a Event::$hasOne[EventPart] attempt would cause the EventPart 
        // to be saved first in the $modelsToSave array, without an event_id
        // We also cannot specify a single event[event_parts][name] item on the event_parts in fields.yaml 
        // We make it jsonable above to prevent the early is_array error for passed attributes
        $eventPart = NULL;
        if ($this->create_event_part) {
            $eventPart = new EventPart();
            $eventPart->fill($this->create_event_part);
            unset($this->create_event_part);
        }
        
        parent::save($options, $sessionKey);
        
        if ($eventPart) {
            // Now we can set the event_id and save
            $eventPart->event()->associate($this);
            $eventPart->save();
        }
    }

    public function getStartAttribute(): Carbon|NULL
    {
        $this->load('event_parts');
        $firstEventPart = $this->event_parts?->sortBy('start')->first();
        return ($firstEventPart ? new Carbon($firstEventPart->start) : NULL);
    }

    public function setStartAttribute(string|NULL $value)
    {
        // Direct Event creation from only a [start] date
        // TODO: Control name, type, status & calendar
        if (is_null($value)) {
            // This is a request to delete the whole Event
            // It never started
            // The FK should have ON DELETE SET NULL, otherwise this will throw an error
            // TODO: The Event will be created even if start is NULL
            // because we are using event[start]
            if ($this->exists) $this->delete();
        } else {
            // Event Part[start]
            $this->load('event_parts');
            if ($firstEventPart = $this->event_parts->first()) {
                // Event needs to be moved, not just the start
                $interval = $firstEventPart->start->diff($firstEventPart->end);
                $firstEventPart->start = new Carbon($value);
                $firstEventPart->end   = (clone $firstEventPart->start)->add($interval);
            } else {
                $firstEventPart = new EventPart;
                $firstEventPart->name   = 'EventPart';
                $firstEventPart->start  = new Carbon($value);
                $firstEventPart->end    = new Carbon($value);
                $firstEventPart->type   = EventType::all()->first();
                $firstEventPart->status = EventStatus::all()->first();
                $this->event_parts = new Collection([$firstEventPart]);
            }
        }

        // Setup the Event
        $this->owner_user = User::authUser();
        $this->calendar   = Calendar::all()->first();
    }
    
    public function delete()
    {
        try {
            EventDeleted::dispatch($this);
        } catch (\Exception $e) {}
        
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
