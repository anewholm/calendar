<?php namespace AcornAssociated\Calendar\Listeners;

use BackendAuth;
use \AcornAssociated\Events\ModelBeforeSave;
use \AcornAssociated\Calendar\Models\EventPart;
use \AcornAssociated\Calendar\Models\Event;
use \AcornAssociated\Calendar\Models\Calendar;
use \AcornAssociated\Calendar\Models\EventType;
use \AcornAssociated\Calendar\Models\EventStatus;
use \AcornAssociated\Collection;
use Carbon\Carbon;

class CompleteCreatedAtEvent
{
    public function handle(ModelBeforeSave &$MBS)
    {
        $model = &$MBS->model;

        /*
        if (isset($model->belongsTo['created_at_event']) && !$model->created_at_event) {
            // TODO: Pass in values for the Event
            // TODO: This system has maybe been superceeded by a database trigger system?
            $modelClass = $model->unqualifiedClassName();
            $name       = $model->name();
            $action     = 'create';

            $auth  = BackendAuth::user();
            $auth->load('user');
            if (!$auth->user) throw new Exception("$auth->login has no associated user for $modelClass::created_at_event calendar event creation");

            $calendar = Calendar::where('name', $modelClass)->first();
            if (!$calendar) {
                $calendar = new Calendar;
                $calendar->name = $modelClass;
                $calendar->owner_user = $auth->user;
                $calendar->save();
            }

            $type = EventType::where('name', ucfirst($action))->first();
            if (!$type) {
                $colour = '';
                $style  = '';

                $type = new EventType;
                $type->name = ucfirst($action);
                $type->colour = $colour;
                $type->style  = $style;
                $type->save();
            }

            $status = EventStatus::getDefault();

            $event = new Event;
            $event->owner_user   = $auth->user; // Maybe NULL
            $event->calendar     = $calendar;
            // Without /update/<id> because it is create
            // see CompleteCreatedAtEventExternalUrl for completion
            $event->external_url = $model->controllerUrl();
            $event->save();

            $eventPart = new EventPart;
            $eventPart->name   = "New $modelClass $name";
            $eventPart->start  = new Carbon();
            $eventPart->end    = new Carbon();
            $eventPart->type   = $type;
            $eventPart->status = $status;
            $eventPart->event  = $event;
            $eventPart->save();

            $model->created_at_event = $event;
        }
        */
    }
}
