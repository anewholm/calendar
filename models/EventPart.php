<?php namespace Acorn\Calendar\Models;

use Acorn\Model;
use Acorn\Calendar\Events\EventUpdated;
use Acorn\Calendar\Events\EventNew;
use Acorn\Calendar\Events\EventDeleted;

use BackendAuth;
use Flash;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Acorn\User\Models\User;
use \Acorn\User\Models\UserGroup;
use \Acorn\Location\Models\Location;
use \Acorn\Calendar\Models\Type;
use \Acorn\Calendar\Models\Instance;
use \Acorn\Exception\DirtyWrite;
use \Acorn\Exception\ObjectIsLocked;
use \Acorn\Messaging\Models\Message;
use \Winter\Storm\Database\Traits\Validation;
use \Winter\Storm\Database\Traits\Nullable;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Collection;
use Winter\Storm\Database\Relations\BelongsTo;
use Winter\Storm\Database\Relations\BelongsToMany;
use Illuminate\Broadcasting\BroadcastException;

class EventPart extends Model
{
    use Validation, Nullable;

    public $table = 'acorn_calendar_event_part';

    protected $nullable = [
        'parent_event_part_id',
        'location_id',
        'alarm',
    ];

    public $rules = [
        //'name'   => ['required', 'min:2'],
        'start'  => 'required',
        'end'    => 'required',
        'type'   => 'required',
        'status' => 'required',
    ];

    public $fillable = [
        'name',
        'description',
        'start',
        'end',
        'until',
        'mask',
        'mask_type',
        'repeat_frequency',
        'repeat',
        // Relations
        'type',
        'users',
        'groups',
        'location',
        'status',
        'alarm',
        // TODO: Should these be fillable?
        'created_at',
        'updated_at',
    ];

    public $belongsTo = [
        'event'    => Event::class,
        'parentEventPart' => self::class,
        'location' => Location::class,
        'type'     => Type::class,
        'status'   => Status::class,
    ];

    public $belongsToMany = [
        'users' => [
            User::class,
            'table' => 'acorn_calendar_event_user',
            'order' => 'name',
        ],
        'groups' => [
            UserGroup::class,
            'table' => 'acorn_calendar_event_user_group',
            'order' => 'name',
        ],
        'userGroups' => [
            UserGroup::class,
            'table' => 'acorn_calendar_event_user_group',
            'order' => 'name',
        ],
    ];

    public $hasMany = [
        'instances' => [
            Instance::class,
            'table' => 'acorn_calendar_instance',
            'order' => 'instance_num',
        ],
    ];

    public $jsonable = [
        'mask' // Gives ["0","2"]
    ];

    public $guarded = [];

    // TODO: Place these in traits also. With a self::$permissionsObject = event
    public function canPast()   { return Event::canPast($this->end); }
    public function canRead()   { return $this->event?->canRead()   && $this->event?->calendar->canRead(); }
    public function canWrite()  { return $this->event?->canWrite()  && $this->event?->calendar->canWrite(); }
    public function canDelete() { return $this->event?->canDelete() && $this->event?->calendar->canDelete(); }
    public function isPast()    { return $this->end < new \DateTime(); }

    public function save(?array $options = [], $sessionKey = null)
    {
        $isNew  = !isset($this->id);
        $result = parent::save($options, $sessionKey);

        // Additional Acorn\Messaging plugin inform
        if (!isset($options['WEBSOCKET']) || $options['WEBSOCKET'] == TRUE) {
            try {
                if ($isNew) EventNew::dispatch($this);
                else        EventUpdated::dispatch($this);
            } catch (BroadcastException $ex) {
                // TODO: Just in case WebSockets not running
                // we demote this to a flash
                Flash::error('WebSockets failed: ' . $ex->getMessage());
            }
        }

        return $result;
    }

    /**
     * Custom encapsulated ORM
     */
    public static function whereHasAllAttendees(Collection $users, ?string $boolean = 'or', ?bool $throwOnEmpty = TRUE)
    {
        // UserGroup is not inherited from our Model
        $groups = UserGroup::whereHas('users', function($q) use($users) {
            return $q->whereIn('id', $users->pluck('id'));
        });

        throw new ApplicationException("whereHasAllAttendees() is not complete");

        return NULL;
    }

    public static function whereHasAttendee(User $user, ?string $boolean = 'or')
    {
        return self::whereHasAllAttendees(new Collection(array($user)), $boolean);
    }

    public static function whereHasBothAttendees(User $user1, User $user2, ?string $boolean = 'or')
    {
        return self::whereHasAllAttendees(new Collection(array($user1, $user2)), $boolean);
    }

    /**
     * Mutators
     */
    protected function start(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
            set: fn ($value) => ($value instanceof \DateTime ? $value->format('Y-m-d h:i:s') : $value),
        );
    }

    protected function end(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
            set: fn ($value) => ($value instanceof \DateTime ? $value->format('Y-m-d h:i:s') : $value),
        );
    }

    protected function repeat(): Attribute
    {
        return Attribute::make(
            // Postgres auto-changes some values
            // They may be user configured in future
            // so let us try to be helpful
            get: fn ($value) => Event::naturalInterval($value),
            set: fn ($value) => ($value ? $value : NULL),
        );
    }

    protected function alarm(): Attribute
    {
        return Attribute::make(
            // Postgres auto-changes some values
            // They may be user configured in future
            // so let us try to be helpful
            get: fn ($value) => Event::naturalInterval($value),
            set: fn ($value) => ($value ? $value : NULL),
        );
    }

    protected function until(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value ? new \DateTime($value) : $value),
            set: fn ($value) => ($value ? $value : NULL),
        );
    }

    protected function mask(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (is_null($value) ? NULL : json_encode(self::dec2binArray($value))),
            set: fn ($value) => ($value ? array_sum(json_decode($value)) : 0),
        );
    }

    protected function instancesDeleted(): Attribute
    {
        // PostGRES integer[]
        return Attribute::make(
            get: fn ($value) => self::integerArrayToPHPArray($value),
            set: fn ($value) => ($value ? self::phpArrayToIntegerArray($value) : NULL),
        );
    }

    public function partIndex()
    {
        // Where is this event_part among the event_parts?
        $event_parts = $this->event->event_parts;

        foreach ($event_parts as $i => $event_part) {
            if ($event_part->id == $this->id) break;
        }

        return $i;
    }

    /*
     * Relationship Accessors
     */
    public function typeClasses()
    {
        $type   = &$this->type;
        $status = $this->status;
        $rt     = preg_replace('/[^a-zA-Z0-9]/', '-', ($this->repeat ? $this->repeatBare() : 'none'));
        $typeName   = preg_replace('/[^a-z0-9]/', '-', strtolower($type->name));
        $statusName = preg_replace('/[^a-z0-9]/', '-', strtolower($status->name));
        $locked     = ($this->locked_by_user_id ? 'is-locked' : '');

        return array(
            "event-type-$type->id",
            "event-type-$typeName",
            "event-part-$this->part",
            "repeat-type-$rt",
            "event-status-$status->id",
            "event-status-$statusName",
            $locked,
        );
    }

    public function typeStyle()
    {
        $type   = &$this->type;
        $status = $this->status;
        return array(
            "background-color:$type->colour",
            "$type->style",
            "$status->style",
        );
    }

    public function repeatBare()
    {
        return ($this->repeat ? preg_replace('/^[0-9]+ */', '', $this->repeat) : NULL);
    }

    public function repeatWithFrequency()
    {
        // 3 * 1 day => 3 days
        return ($this->repeat
            ? "$this->repeat_frequency " .
              $this->repeatBare() .
              ($this->repeat_frequency > 1 ? 's' : '')
            : NULL
        );
    }

    public function groupsList()
    {
        $groups = '';
        foreach ($this->groups as $id => $group) {
            $comma   = ($groups ? ', ' : '');
            $groups .= "$comma$group->name";
        }
        return $groups;
    }

    public function usersList()
    {
        $users = '';
        foreach ($this->users as $id => $user) {
            $comma   = ($users ? ', ' : '');
            $users  .= "$comma$user->name";
        }
        return $users;
    }

    public function attendees()
    {
        $groups = $this->groupsList();
        $users  = $this->usersList();
        $comma  = ($groups && $users ? ', ' : '');
        return "$groups$comma$users";
    }

    public function getRepeatOptions()
    {
        // TODO: Make this configurable?
        // These are PostGreSQL specific time strings
        return array(
            ''        => trans('acorn.calendar::lang.models.eventpart.repeat_type.none'),
            '1 day'   => trans('acorn.calendar::lang.models.eventpart.repeat_type.daily'),
            '1 week'  => trans('acorn.calendar::lang.models.eventpart.repeat_type.weekly'),
            '1 month' => trans('acorn.calendar::lang.models.eventpart.repeat_type.monthly'),
            '1 year'  => trans('acorn.calendar::lang.models.eventpart.repeat_type.yearly'),
        );
    }

    public function getAlarmOptions()
    {
        // TODO: Make this configurable?
        // These are PostGreSQL specific time strings
        return array(
            ''           => trans('acorn.calendar::lang.models.eventpart.alarm_type.none'),
            '00:00:00'   => trans('acorn.calendar::lang.models.eventpart.alarm_type.at_the_event_time'),
            '5 minutes'  => trans('acorn.calendar::lang.models.eventpart.alarm_type.5_minutes'),
            '10 minutes' => trans('acorn.calendar::lang.models.eventpart.alarm_type.10_minutes'),
            '15 minutes' => trans('acorn.calendar::lang.models.eventpart.alarm_type.15_minutes'),
            '30 minutes' => trans('acorn.calendar::lang.models.eventpart.alarm_type.30_minutes'),
            '1 hour'     => trans('acorn.calendar::lang.models.eventpart.alarm_type.1_hour'),
            '2 hours'    => trans('acorn.calendar::lang.models.eventpart.alarm_type.2_hours'),
            '5 hours'    => trans('acorn.calendar::lang.models.eventpart.alarm_type.5_hours'),
            '12 hours'   => trans('acorn.calendar::lang.models.eventpart.alarm_type.12_hours'),
            '1 day'      => trans('acorn.calendar::lang.models.eventpart.alarm_type.1_day'),
            '2 days'     => trans('acorn.calendar::lang.models.eventpart.alarm_type.2_days'),
        );
    }

    /*
     * Filter helpers
     */
    static public function groupsAll()
    {
        $groups = array();
        foreach (UserGroup::all() as $group) {
            $groups[$group->id] = $group->name;
        }
        return $groups;
    }

    static public function usersAll()
    {
        $users = array();
        foreach (User::all() as $user) {
            $users[$user->id] = $user->name;
        }
        return $users;
    }
}
