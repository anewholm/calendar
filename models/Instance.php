<?php namespace AcornAssociated\Calendar\Models;

use Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Model
 */
class Instance extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $belongsTo = [
        'eventPart' => EventPart::class,
    ];

    public $table = 'acornassociated_calendar_instance';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [];

    /**
     * Mutators
     */
    public function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    public function instanceStart(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    public function instanceEnd(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new \DateTime($value),
            set: fn ($value) => $value->format('Y-m-d H:i'),
        );
    }

    /**
     * Characteristics
     */
    public function continueStart()
    {
        return $this->instance_start < $this->date;
    }

    public function continueEnd()
    {
        return $this->instance_end >= (clone $this->date)->modify('+1 day');
    }

    public function allDay()
    {
        return $this->continueStart() && $this->continueEnd();
    }

    public function typeClasses()
    {
        $classes = array(
            ($this->continueStart() ? 'continue-start' : 'has-start'),
            ($this->continueEnd()   ? 'continue-end'   : 'has-end')
        );
        if ($this->allDay()) array_push($classes, 'all-day');
        return $classes;
    }

    public function bubbleHelp()
    {
        $eventpart = &$this->eventPart;
        $type      = &$eventpart->type;
        $start     = $this->instance_start->format('H:i');
        $end       = $this->instance_end->format('H:i');
        $rwf       = $eventpart->repeatWithFrequency();

        $help  = "$type->name: $eventpart->name\n";
        $help .= ($type->whole_day ? trans('whole day') : "$start =&gt; $end") . "\n";
        if ($eventpart->repeat) $help .= trans('repeats every') . " $rwf\n";
        if ($location  = $eventpart->location) $help .= "@ $location->name\n";
        if ($attendees = $eventpart->attendees()) $help .= "with $attendees\n";

        return $help;
    }
}
