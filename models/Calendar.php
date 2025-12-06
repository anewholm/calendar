<?php namespace Acorn\Calendar\Models;

use Acorn\Model;
use \Acorn\User\Models\User;
use \Acorn\User\Models\UserGroup;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Calendar extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;
    use \Acorn\Traits\LinuxPermissions;

    public $table = 'acorn_calendar_calendars';

    protected $nullable = [
        'owner_user_group_id',
    ];

    public $translatable = [
        'name',
        'description'
    ];

    public $fillable = [
        'name',
        'description',
        'sync_file',
        'sync_format',
        'created_at',
        'updated_at',
        'permissions',
        // Relations
        'owner_user',
        'owner_user_group',
    ];

    public $guarded = [];

    public $rules = [
    ];

    public $belongsTo = [
        'owner_user' => User::class,
        'owner_user_group' => UserGroup::class,
    ];

    public $hasMany = [
        'events' => [
            Event::class,
            'table' => 'acorn_calendar_events',
        ],
    ];

    public $jsonable = ['permissions'];

    public static function getDefault(): ?Calendar
    {
        return self::where('name', 'Default')->first();
    }

    protected function permissions(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => array_sum(json_decode($value)),
        );
    }

    public function syncFiles(): string|null
    {
        $message = NULL;

        // Write external foreign ICS calendar files with new data
        if ($syncFile = $this->sync_file) {

            // TODO: Write ICS calendar header and timezones
            $default_time_zone = Settings::get('default_time_zone');

            switch ($this->sync_format) {
                case 0: // ICS
                    $output = "BEGIN:VCALENDAR
PRODID:-//Mozilla.org/NONSGML Mozilla Calendar V1.1//EN
VERSION:2.0

BEGIN:VTIMEZONE
TZID:Asia/Damascus

BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:20180330T000000
RDATE:20180330T000000
RDATE:20190329T000000
RDATE:20200327T000000
RDATE:20210326T000000
RDATE:20220325T000000
END:DAYLIGHT

BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19700101T000000
RDATE:19700101T000000
RDATE:20181026T000000
RDATE:20191025T000000
RDATE:20201030T000000
RDATE:20211029T000000
END:STANDARD

BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:+03
DTSTART:20221028T000000
RDATE:20221028T000000
END:STANDARD
END:VTIMEZONE\n\n";

                    // TODO: This ICS output is very time consuming
                    // it should be a separate thread
                    // TODO: Concurrent access locking mutex?
                    $events = &$this->events;
                    foreach ($events as $event) {
                        foreach ($event->event_parts as $part) {
                            foreach ($part->instances as $instance) {
                                $output .= $instance->format($this->sync_format);
                            }
                        }
                    }
                    $output .= "END:VCALENDAR\n";

                    // TODO: Error checking of file write
                    file_put_contents($syncFile, $output);

                    $docroot    = app()->basePath();
                    $host       = $_SERVER['HTTP_HOST'];
                    $relative   = str_replace($docroot, '', $syncFile);
                    $location   = "https://$host$relative";
                    $eventCount = count($events);
                    $writtenTo  = trans('acorn.calendar::lang.models.calendar.events_written_to');
                    $message    = "$eventCount $writtenTo $location (ICS)";
                    break;
            }
        }

        return $message;
    }

    public static function menuitemCount(): mixed {
        # Auto-injected by acorn-create-system
        return Model::menuitemCountFor(self::class);
    }
}
