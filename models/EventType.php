<?php namespace Acorn\Calendar\Models;

use Acorn\Model;

/**
 * Model
 */
class EventType extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'acorn_calendar_event_types';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [];

    public static function getDefault(): ?EventType
    {
        return self::where('name', 'Normal')->first();
    }
}
