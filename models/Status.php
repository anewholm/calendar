<?php namespace AcornAssociated\Calendar\Models;

use Model;

/**
 * Model
 */
class Status extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'acornassociated_calendar_event_status';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
    
    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    public $jsonable = [];
}
