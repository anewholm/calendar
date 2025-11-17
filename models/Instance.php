<?php namespace Acorn\Calendar\Models;

use Acorn\Model;
use Acorn\Models\Server;
use Illuminate\Database\Eloquent\Casts\Attribute;
use BackendAuth;
use Backend;
use Acorn\User\Models\User;
use Acorn\User\Models\UserGroup;
use Winter\Storm\Database\Collection;
use \Acorn\Messaging\Models\Message;
use ApplicationException;

class Instance extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $belongsTo = [
        'eventPart' => EventPart::class,
    ];

    // TODO: This should be extend() in the Messaging Plugin.php
    public $belongsToMany = [
        'messages' => [
            Message::class,
            'table' => 'acorn_messaging_message_instance',
            'order' => 'created_at',
        ],
    ];

    public $table = 'acorn_calendar_instances';

    public $rules = [];
    public $jsonable = [];

    /**
     * Custom encapsulated ORM
     */
    public static function whereHasAllAttendees(Collection $users, string $boolean = 'or', bool $throwOnEmpty = TRUE)
    {
        // EventParts can have users, user_groups and 1 user_group_version
        // used depending on the User settings
        // TODO: JOIN this rather than IN()
        $eventParts = EventPart::whereHasAllAttendees($users, $boolean, $throwOnEmpty);
        return self::whereHas('eventPart', function($q) use($eventParts) {
            return $q->whereIn('id', $eventParts->pluck('id'));
        });
    }
    
    public static function whereHasAttendee(User $user, string $boolean = 'or')
    {
        return self::whereHasAllAttendees(new Collection(array($user)), $boolean);
    }

    public static function whereHasBothAttendees(User $user1, User $user2, string $boolean = 'or')
    {
        return self::whereHasAllAttendees(new Collection(array($user1, $user2)), $boolean);
    }


    /**
     * Security
     */
    public function canPast()   { return Event::canPast($this->instance_end); }
    public function canRead()   { return $this->eventPart?->canRead(); }
    public function canDelete() { return $this->eventPart?->canDelete() && $this->canPast(); }
    public function canWrite()  { return $this->eventPart?->canWrite()  && $this->canPast(); }

    /**
     * Mutators
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Backend::makeCarbon($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    protected function instanceStart(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Backend::makeCarbon($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    protected function instanceEnd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Backend::makeCarbon($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    /**
     * Characteristics
     */
    public function messageCount()
    {
        return (class_exists(Message::class) ? count($this->messages) : NULL);
    }

    public function continueStart()
    {
        return $this->instance_start < $this->date;
    }

    public function continueEnd()
    {
        return $this->instance_end >= (clone $this->date)->modify('+1 day');
    }

    public function isLast()
    {
        // Is the last instance in a repitition
        $isLast = NULL;

        $eventPart = &$this->eventPart;
        $isFiniteRepitition = ($eventPart->repeat && $eventPart->until);
        if ($isFiniteRepitition) {
            $instance_last = $eventPart->instances->last();
            $isLast = ($this->id == $instance_last->id);
        }

        return $isLast;
    }

    public function allDay()
    {
        return $this->continueStart() && $this->continueEnd();
    }

    public function typeClasses()
    {
        $classes = array(
            ($this->continueStart() ? 'continue-start' : 'has-start'),
            ($this->continueEnd()   ? 'continue-end'   : 'has-end'),
            ($this->messageCount()  ? 'has-messages'   : ''),
        );
        if ($this->allDay()) array_push($classes, 'all-day');
        return $classes;
    }

    public function bubbleHelp()
    {
        $help      = '';
        $eventpart = &$this->eventPart;
        $type      = &$eventpart->type;
        $status    = &$eventpart->status;
        $start     = $this->instance_start->format('H:i');
        $end       = $this->instance_end->format('H:i');
        $rwf       = $eventpart->repeatWithFrequency();

        // Cut-off near last word
        $eventNameFormat = $eventpart->name;
        if (strlen($eventNameFormat) > 150) {
            $eventNameFormat = substr($eventNameFormat, 0, 150);
            $eventNameFormat = preg_replace('/ +[^ ]{0,8}$/', '', $eventNameFormat);
            $eventNameFormat = "$eventNameFormat ...";
        }

        if ($type->id != 1) $help .= "$type->name: ";
        $help .= "$eventNameFormat\n";
        $help .= ($type->whole_day 
            ? trans('acorn.calendar::lang.models.eventtype.whole_day') 
            : "$start =&gt; $end"
        ) . "\n";
        if ($eventpart->repeat) $help .= trans('acorn.calendar::lang.models.eventpart.repeats_every') . " $rwf\n";
        if ($location  = $eventpart->location) $help .= "@ $location->name\n";
        if ($attendees = $eventpart->attendees()) $help .= "with $attendees\n";
        if ($status->id != 1) $help .= "$status->name\n";

        return $help;
    }

    public function format(int $format = 0)
    {
        $output    = NULL;
        $eventPart = &$this->eventPart;
        $status    = &$eventPart->status;

        switch ($format) {
            case 0: // ICS
                $dateFormatIcs   = 'Ymd\THis';
                $dateFormatIcsTZ = $dateFormatIcs . 'Z';
                $created   = $this->created_at->format($dateFormatIcsTZ);
                $updated   = $this->updated_at?->format($dateFormatIcsTZ) ?: $created;
                $uuid      = "dfc99471-9e6f-4d5d-ab3e-f94ea4abf6$this->id"; // 2 digit id
                $name      = ($eventPart->name 
                    ? preg_replace('/[\\n\\r]+/', ' ', $eventPart->name)
                    : $this->id
                );
                $description = ($eventPart->description 
                    ? preg_replace('/[\\n\\r]+|<[^>]+>/', ' ', $eventPart->description)
                    : ''
                );
                // TODO: Make ICS timezone configurable
                $tz        = 'Asia/Damascus';
                $start     = $this->instance_start->format($dateFormatIcs);
                $end       = $this->instance_end->format(  $dateFormatIcs);
                // TODO: Other ICS event properties: priority, etc.
                $cancelled = ($status->id == 4);
                $tentative = ($status->id == 3);
                // 00:05:00 => PT5M
                $alarm     = Event::intervalToPeriod($eventPart->alarm); 

                $output = "BEGIN:VEVENT
                    CREATED:$created
                    LAST-MODIFIED:$updated
                    DTSTAMP:$updated
                    UID:$uuid
                    SUMMARY:$name
                    DTSTART;TZID=$tz:$start
                    DTEND;TZID=$tz:$end\n";
                if ($description) $output .= "DESCRIPTION:$description\n";
                
                // TODO: What are these ICS properties?
                $output .= "TRANSP:OPAQUE
                    SEQUENCE:1
                    X-MOZ-GENERATION:1\n";

                if ($cancelled) $output .= "METHOD:CANCEL
                    STATUS:CANCELLED\n";
                if ($tentative) $output .= "STATUS:TENTATIVE\n";
                if ($alarm)     $output .= "BEGIN:VALARM
                    TRIGGER:-$alarm
                    ACTION:DISPLAY
                    DESCRIPTION:Reminder
                    END:VALARM\n";

                $output .= "END:VEVENT\n\n";

                // Remove leading spaces
                $output = preg_replace('/^\s+/m', '', $output);
                break;
        }

        return $output;
    }
}
