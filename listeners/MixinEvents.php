<?php namespace AcornAssociated\Calendar\Listeners;

use AcornAssociated\Messaging\Events\MessageListReady;
use AcornAssociated\Messaging\Models\Message;
use AcornAssociated\Calendar\Models\Instance;
use BackendAuth;
use Carbon\Carbon;
use Winter\Storm\Database\Collection;

class MixinEvents
{
    public function handle(MessageListReady $MLR)
    {
        // Get events that both users attended
        $authUser  = &$MLR->authUser;
        $withUser  = &$MLR->withUser;
        $mixins    = array();
        $now       = new Carbon();
        $instances = Instance::where('instance_end', '<', $now); // TODO: where($authUser, $withUser);

        foreach ($instances->get() as &$instance) {
            $eventPart = &$instance->eventPart;
            $type      = &$eventPart->type;
            $message   = new Message(array(
                'user_from'  => $authUser,
                'subject'    => $eventPart->name,
                'body'       => "You and $withUser->first_name attended this event",
                'users'      => array($withUser),
                'groups'     => array(),
                'roles'      => array(),
                'created_at' => $instance->instance_start,
                'source'     => 'event',
                'labels'     => strtolower($type->name),
            ));
            $message->id  = "instance-$instance->id";
            $message->url = "/backend/acornassociated/calendar/calendar#!/instance/$instance->id";
            array_push($mixins, $message);
        }

        return new Collection($mixins);
    }
}
