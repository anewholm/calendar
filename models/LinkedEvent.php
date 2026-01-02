<?php namespace Acorn\Calendar\Models;

use Acorn\Model;
use \Acorn\User\Models\User;
use \Acorn\User\Models\UserGroup;
use \Acorn\Location\Models\Location;
use \Acorn\Calendar\Models\EventType;
use \Acorn\Calendar\Models\Instance;

class LinkedEvent extends Model
{
    public $table = 'acorn_calendar_linked_events';

    public $morphTo = [
        // The associated model for this event
        // that holds the *_event_id column pointing to the event
        'model' => []
    ];

    public function modelExists(): bool
    {
        return class_exists($this->attributes['model_type']);
    }
}
