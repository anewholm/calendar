<?php namespace Acorn\Calendar\Listeners;

use Acorn\Messaging\Events\MessageListReady;
use Acorn\Messaging\Models\Message;
use Acorn\Calendar\Models\Instance;
use Acorn\Calendar\Models\EventPart;
use Acorn\Calendar\Models\EventStatus;
use BackendAuth;
use Acorn\IntlCarbon;
use Winter\Storm\Database\Collection;
use Acorn\User\Models\User;
use Acorn\User\Models\UserGroup;

class MixinEvents
{
    public function handle(MessageListReady $MLR)
    {
        // Get events that both users attended
        $authUser  = &$MLR->authUser;
        $withUser  = &$MLR->withUser;
        $mixins    = array();
        
        
        // TODO: This should only show events that both the authUser and the withUser are attending
        // including their groups
        
        // whereHas system
        /*
        $now    = new IntlCarbon();
        $users  = new Collection(array($authUser, $withUser));
        $groups = $authUser->groups()->get()->add( 
            $withUser->groups()->get()
        );
        $cancelled = EventStatus::cancelled();
        $instances1 = Instance::select()
            ->whereHas('eventPart.users',    function($query) use ($users) {
                $query->whereIn('id', $users->pluck('id'));
            })
            ->orWhereHas('eventPart.groups', function($query) use ($groups) {
                $query->whereIn('id', $groups->pluck('id'));
            })
            ->whereDoesntHave('eventPart.status', function($query) use ($cancelled) {
                $query->where('id', '=', $cancelled->id);
            });

        // ORM belongsTo*() system
        $eps = EventPart::whereBelongsToAny([$users, $groups]);
        $instances2 = Instance::whereBelongsTo($eps->get());
        // Or
        $instances3 = Instance::whereBelongsToAnyThrough(
            EventPart::class,
            [$users, $groups],
        );
        // Or encapsulated ORM (prefrerred system)
        // This simply has:
        //   Instance::whereBelongsToAnyThrough(
        //      EventPart::class,
        //      [$users, $groups],
        //  );
        // encapsualted in a method on Instance and EventPart
        $instances = Instance::whereHasBothAttendees($authUser, $withUser)
            ->where('instance_end', '<', $now)
            ->where('status_id', '!=', $cancelled->id);
            //->orderBy('instance_start');
        */

        $instances = Instance::all();
        foreach ($instances as &$instance) {
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
            $message->url = "/backend/acorn/calendar/calendar#!/instance/$instance->id";
            array_push($mixins, $message);
        }

        return new Collection($mixins);
    }
}
