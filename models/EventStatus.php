<?php namespace Acorn\Calendar\Models;

use Acorn\Model;

/**
 * Model
 */
class EventStatus extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    // Sysytem statuses. Cannot be deleted
    // see seeding in updates
    const NORMAL    = 1;
    const CANCELLED = 2;
    const TENTATIVE = 3;
    const CONFLICT  = 4;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'acorn_calendar_event_statuses';

    public $translatable = [
        'name',
        'description'
    ];

    public $rules = [];
    public $jsonable = [];

    public static function cancelled()
    {
        // We create the object in memory only for efficiency
        static $cancelled = new EventStatus();
        $cancelled->id = self::CANCELLED;
        return $cancelled;
    }

    public static function getDefault(): ?EventStatus
    {
        return self::where('name', 'Normal')->first();
    }

    public static function menuitemCount(): mixed {
        # Auto-injected by acorn-create-system
        return Model::menuitemCountFor(self::class);
    }
}
