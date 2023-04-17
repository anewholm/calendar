<?php namespace AcornAssociated\Calendar\Models;

use Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use \AcornAssociated\Location\Models\Location;
use \AcornAssociated\Calendar\Models\Type;
use \AcornAssociated\Calendar\Models\Instance;

trait DeepReplicates {
    // TODO: Move this Trait in the modules/acornassociated
    /*
    public function replicate(?array $except = null)
    {
        // Replicate relations recursively also
        $copy = parent::replicate($except);
        $copy->push();

        // TODO: Relations will not be loaded yet
        foreach ($this->getRelations() as $relation => $entries) {
            foreach($entries as $entry) {
                $e = $entry->replicate($except);
                if ($e->push()) {
                    $clone->{$relation}()->save($e);
                }
            }
        }
    }
    */
}

class EventPart extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use DeepReplicates;

    public $table = 'acornassociated_calendar_event_part';

    protected $nullable = [
        'parent_event_part_id',
        'location_id',
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
        'created_at',
        'updated_at',
        'repeat_frequency',
        'repeat',
        // Relations
        'type',
        'status',
        'parent_event_part',
        'location',
        'users',
        'groups',
    ];

    public $belongsTo = [
        'event'    => Event::class,
        'parent_event_part' => self::class,
        'location' => Location::class,
        'type'     => Type::class,
        'status'   => Status::class,
    ];

    public $belongsToMany = [
        'users' => [
            User::class,
            'table' => 'acornassociated_calendar_event_user',
            'order' => 'first_name',
        ],
        'groups' => [
            UserGroup::class,
            'table' => 'acornassociated_calendar_event_user_group',
            'order' => 'name',
        ],
    ];

    public $hasMany = [
        'instances' => [
            Instance::class,
            'table' => 'acornassociated_calendar_instance',
            'order' => 'instance_id',
        ],
    ];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [
        'mask' // Gives ["0","2"]
    ];

    public $guarded = [];

    public function isPast()    { return $this->end < new \DateTime(); }
    public function canPast()   { return Event::canPast($this->end); }
    public function canRead()   { return $this->event?->canRead(); }
    public function canWrite()  { return $this->event?->canWrite(); }
    public function canDelete() { return $this->event?->canDelete(); }

    /**
     * Mutators
     */
    public function start(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
        );
    }

    public function end(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
        );
    }

    public function repeat(): Attribute
    {
        return Attribute::make(
            // Postgres auto-changes some values
            get: fn ($value) => ($value == '7 days' ? '1 week'  :
                                ($value == '1 mon'  ? '1 month' :
                                $value)),
            set: fn ($value) => ($value ? $value : NULL),
        );
    }

    public function until(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value ? new \DateTime($value) : $value),
            set: fn ($value) => ($value ? $value : NULL),
        );
    }

    public function mask(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => (is_null($value) ? NULL : json_encode(self::dec2binArray($value))),
            set: fn ($value) => ($value ? array_sum(json_decode($value)) : 0),
        );
    }

    public function instancesDeleted(): Attribute
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
        return array(
            "event-type-$type->id",
            "event-type-$typeName",
            "event-part-$this->part",
            "repeat-type-$rt",
            "event-status-$status->id",
            "event-status-$statusName",
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
            $users  .= "$comma$user->first_name";
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
        return array(
            ''        => 'None',
            '1 day'   => 'Daily',
            '1 week'  => 'Weekly',
            '1 month' => 'Monthly',
            '1 year'  => 'Yearly',
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
            $users[$user->id] = $user->first_name;
        }
        return $users;
    }

    /*
     * Private utilities
     * TODO: Place these PostGRES utilities in a base Model class
     */
    static protected function dec2binArray(int $dec)
    {
        $result = array();
        $bin    = decbin($dec);
        $len    = strlen($bin);
        for ($i = 0; $i < $len; $i++)
        {
            if ($bin[$len -$i-1] == '1') array_push($result, 2**$i);
        }
        return $result;
    }

    static protected function integerArrayToPHPArray(?string $integerArray, ?bool $forceArray = TRUE)
    {
        // {2,3,4}
        return ($integerArray
            ? array_map('intval', explode(',', preg_replace('/^{|}$/', '', $integerArray)))
            : ($forceArray ? array() : NULL)
        );
    }

    static protected function phpArrayToIntegerArray(array $phpArray, ?bool $forceArray = TRUE)
    {
        // {2,3,4}
        return ($phpArray
            ? '{' . implode(',', $phpArray) . '}'
            : ($forceArray ? "{}" : NULL)
        );
    }
}
