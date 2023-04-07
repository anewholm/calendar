<?php namespace AcornAssociated\Calendar\Models;

use Model;
use BackendAuth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use \Backend\Models\User;
use \Backend\Models\UserGroup;
use \AcornAssociated\Location\Models\Location;
use \AcornAssociated\Calendar\Models\Type;
use \AcornAssociated\Calendar\Models\Instance;
use \Illuminate\Auth\Access\AuthorizationException;

/**
 * Model
 */
class Event extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;

    // TODO: Move all this in to an AA Trait
    public static $READ   = 1;
    public static $WRITE  = 2;
    public static $DELETE = 4;

    public static $USER   = 1;
    public static $GROUP  = 8;
    public static $OTHER  = 64;

    public $table = 'acornassociated_calendar_event';

    protected $nullable = [
        'owner_user_group_id',
    ];

    public $rules = [
    ];

    public $fillable = [
        'created_at',
        'updated_at',
        // Relations
        'calendar',
        'owner_user',
        'owner_user_group',
        'permissions',
    ];

    public $belongsTo = [
        'calendar'   => Calendar::class,
        'owner_user' => User::class,
        'owner_user_group' => UserGroup::class,
    ];

    public $hasMany = [
        'event_parts' => [
            EventPart::class,
            'table' => 'acornassociated_calendar_event_part',
            'order' => 'start',
        ],
    ];

    public $jsonable = ['permissions'];

    public $guarded = [];

    protected function can(int $accessType)
    {
        $user   = BackendAuth::user();
        $groups = $user->groups->keyBy('id');

        // TODO: isSuperUser
        $isOwner = ($user->id == $this->owner_user->id);
        $inGroup = ($this->owner_user_group && $groups->get($this->owner_user_group->id));

        return ($isOwner && $this->permissions & $accessType * self::$USER)
            || ($inGroup && $this->permissions & $accessType * self::$GROUP)
            ||              $this->permissions & $accessType * self::$OTHER;
    }

    public function canRead()   { return $this->can(self::$READ); }
    public function canWrite()  { return $this->can(self::$WRITE); }
    public function canDelete() { return $this->can(self::$DELETE); }

    // TODO: SECURITY: Read security
    public function delete()
    {
        if (!$this->canDelete()) throw new AuthorizationException('Cannot delete this object');

        return parent::delete();
    }

    public function fill(array $attributes)
    {
        // This works on the original values, before fill()
        if ($this->attributes && !$this->canWrite()) throw new AuthorizationException('Cannot write this object');
        return parent::fill($attributes);
    }

    public function save(?array $options = [], $sessionKey = null)
    {
        // This works on the new values, because after fill()
        if (!$this->canWrite()) throw new AuthorizationException('Cannot write this object');
        return parent::save($options, $sessionKey);
    }

    public function permissions(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => array_sum(json_decode($value)),
        );
    }
}
