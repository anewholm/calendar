<?php namespace Acorn\Calendar\Listeners;

use \Acorn\Events\ModelAfterSave;

class CompleteCreatedAtEventExternalUrl
{
    public function handle(ModelAfterSave &$MAS)
    {
        $model = &$MAS->model;

        if (isset($model->belongsTo['created_at_event'])) {
            $model->load('created_at_event');
            if ($event = $model->created_at_event) {
                $event->external_url = $model->controllerUrl('update', $model->id());
                $event->save();
            }
        }
    }
}
