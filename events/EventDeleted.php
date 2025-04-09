<?php namespace Acorn\Calendar\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

use Acorn\Calendar\Models\Event;

class EventDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $event;

    public function __construct(Event $_event)
    {
        // We should create a copy of the basic values
        // because this event is already deleted
        // and its Model parts will trigger attribute queries
        $this->event = $_event;
    } 

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return [
            new Channel('calendar')
        ];
    }

    public function broadcastAs()
    {
        return 'event.deleted';
    }
}