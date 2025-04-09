<?php namespace AcornAssociated\Calendar\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

use AcornAssociated\Calendar\Models\EventPart;

class EventUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $eventPart;

    public function __construct(EventPart $_eventPart)
    {
        $this->eventPart = $_eventPart;
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
        return 'event.updated';
    }
}