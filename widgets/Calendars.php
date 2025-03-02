<?php namespace Acorn\Calendar\Widgets;

use Db;
use Str;
use Html;
use Lang;
use Backend;
use DbDongle;
use Carbon\Carbon;
use Winter\Storm\Html\Helper as HtmlHelper;
use Winter\Storm\Router\Helper as RouterHelper;
use System\Helpers\DateTime as DateTimeHelper;
use System\Classes\PluginManager;
use System\Classes\MediaLibrary;
use System\Classes\ImageResizer;
use Backend\Classes\WidgetBase;
use Winter\Storm\Database\Model;
use ApplicationException;
use \Illuminate\Auth\Access\AuthorizationException;
use BackendAuth;
use Backend\Models\User;
use Backend\Widgets\Filter;

use Exception;
use Acorn\Calendar\Widgets\CalendarCell;
use Acorn\Calendar\Models\Calendar;
use Acorn\Calendar\Models\Event;
use Acorn\Calendar\Models\EventPart;
use Acorn\Calendar\Models\Instance;
use Acorn\Calendar\Models\Settings;
use Request;
use Flash;

/**
 * Calendar Widget
 * Used for building back end calendars, renders a calendar of model objects
 *
 * @package acorn/calendar
 * @author sz
 */
class Calendars extends WidgetBase
{
    use Backend\Traits\PreferenceMaker;

    //
    // Configurable properties
    //
    public $columns;
    public $model;

    public $recordUrl;
    public $recordOnClick;
    public $noRecordsMessage = 'backend::lang.calendar.no_records';

    public $showCheckboxes = false;
    public $showSetup = false;
    public $showPagination = false;
    public $showPageNumbers = false;

    public $customViewPath;

    //
    // Object properties
    //
    protected $defaultAlias = 'calendar';

    /**
     * @var array Collection of all calendar columns used in this calendar.
     * @see Backend\Classes\CalendarCell
     */
    protected $allColumns;

    /**
     * @var array Override default columns with supplied key names.
     */
    protected $columnOverride;

    /**
     * @var array Columns to display and their order.
     */
    protected $visibleColumns;

    /**
     * @var array Model data collection.
     */
    protected $records;

    protected $defaultSort = 'date';

    /**
     * @var array Model weeks hierarchy.
     * weeks => days => events
     */
    protected $weeks;

    /**
     * @var string If searching the records, specifies a policy to use.
     * - all: result must contain all words
     * - any: result can contain any word
     * - exact: result must contain the exact phrase
     */
    protected $searchMode;
    protected $searchTerm;
    protected $searchScope;

    /**
     * @var array Collection of functions to apply to each calendar query.
     */
    protected $filterCallbacks = [];

    /**
     * @var array Calendar of CSS classes to apply to the calendar container element
     */
    public $cssClasses = [];

    /*
    public function __construct($controller, $configuration = [])
    {
        dd($configuration);
        parent::__construct($controller, $configuration);
    }
    */

    /**
     * Initialize the widget, called by the constructor and free from its parameters.
     */
    public function init()
    {
        // TODO: config is already completed here. Why? dd($this->config);
        // ~/controllers/calendars/config_form.yaml
        $this->fillFromConfig([
            'columns',
            'model',
            'recordUrl',
            'recordOnClick',
            'noRecordsMessage',
            'showPageNumbers',
            'recordsPerPage',
            'perPageOptions',
            'showSorting',
            'defaultSort',
            'showCheckboxes',
            'showSetup',
            'treeExpanded',
            'showPagination',
            'customViewPath',
        ]);

        /*
         * Configure the calendar widget
         */
        if ($this->customViewPath) {
            $this->addViewPath($this->customViewPath);
        }

        $this->validateModel();
    }

    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addJs('/modules/acorn/assets/js/acorn.js');
        $this->addJs('js/acorn.calendar.js', 'core');
        $this->addCss('css/acorn.calendar.css', 'core');
    }

    /**
     * Renders the widget.
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('calendar-container');
    }

    /**
     * Prepares the calendar data
     */
    public function prepareVars()
    {
        $this->vars['cssClasses'] = implode(' ', $this->cssClasses);
        $this->vars['columns'] = $this->getVisibleColumns();
        $this->vars['columnTotal'] = $this->getTotalColumns();
        $this->vars['records'] = $this->getRecords();
        $this->vars['showCheckboxes'] = $this->showCheckboxes;

        $this->organiseRecords();
    }

    /**
     * Event handler for refreshing the calendar.
     */
    public function onRefresh()
    {
        $this->prepareVars();
        return ['#'.$this->getId() => $this->makePartial('calendar')];
    }

    public function onWebSocket()
    {
        return $this->onRefresh();
    }

    public function onFilter()
    {
        return $this->onRefresh();
    }

    /**
     * Validate the supplied form model.
     * @return void
     */
    protected function validateModel()
    {
        if (!$this->model) {
            throw new ApplicationException(Lang::get(
                'backend::lang.calendar.missing_model',
                ['class'=>get_class($this->controller)]
            ));
        }

        if (!$this->model instanceof Model) {
            throw new ApplicationException(Lang::get(
                'backend::lang.model.invalid_class',
                ['model'=>get_class($this->model), 'class'=>get_class($this->controller)]
            ));
        }

        return $this->model;
    }

    /**
     * Replaces the @ symbol with a table name in a model
     * @param  string $sql
     * @param  string $table
     * @return string
     */
    protected function parseTableName($sql, $table)
    {
        return str_replace('@', $table.'.', $sql);
    }

    /**
     * Applies any filters to the model.
     */
    public function prepareQuery()
    {
        $query = $this->model->newQuery();
        $primaryTable = $this->model->getTable();
        $selects = [$primaryTable.'.*'];
        $joins = [];
        $withs = [];
        $bindings = [];

        /**
         * @event backend.calendar.extendQueryBefore
         * Provides an opportunity to modify the `$query` object before the Calendar widget applies its scopes to it.
         *
         * Example usage:
         *
         *     Event::calendaren('backend.calendar.extendQueryBefore', function ($calendarWidget, $query) {
         *         $query->whereNull('deleted_at');
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.extendQueryBefore', function ($query) {
         *         $query->whereNull('deleted_at');
         *     });
         *
         */
        $this->fireSystemEvent('backend.calendar.extendQueryBefore', [$query]);

        /*
         * Prepare searchable column names
         */
        $primarySearchable = [];
        $relationSearchable = [];

        $columnsToSearch = [];
        if (
            strlen($this->searchTerm) !== 0
            && trim($this->searchTerm) !== ''
            && ($searchableColumns = $this->getSearchableColumns())
        ) {
            foreach ($searchableColumns as $column) {
                /*
                 * Related
                 */
                if ($this->isColumnRelated($column)) {
                    $table = $this->model->makeRelation($column->relation)->getTable();
                    $columnName = isset($column->sqlSelect)
                    ? DbDongle::raw($this->parseTableName($column->sqlSelect, $table))
                    : $table . '.' . $column->valueFrom;

                    $relationSearchable[$column->relation][] = $columnName;
                }
                /*
                 * Primary
                 */
                else {
                    $columnName = isset($column->sqlSelect)
                    ? DbDongle::raw($this->parseTableName($column->sqlSelect, $primaryTable))
                    : DbDongle::cast(Db::getTablePrefix() . $primaryTable . '.' . $column->columnName, 'TEXT');

                    $primarySearchable[] = $columnName;
                }
            }
        }

        /*
         * Prepare related eager loads (withs) and custom selects (joins)
         */
        foreach ($this->getVisibleColumns() as $column) {
            // If useRelationCount is enabled, eager load the count of the relation into $relation_count
            if ($column->relation && ($column->config['useRelationCount'] ?? false)) {
                $query->withCount($column->relation);
            }

            if (!$this->isColumnRelated($column) || (!isset($column->sqlSelect) && !isset($column->valueFrom))) {
                continue;
            }

            if (isset($column->valueFrom)) {
                $withs[] = $column->relation;
            }

            $joins[] = $column->relation;
        }

        /*
         * Add eager loads to the query
         */
        if ($withs) {
            $query->with(array_unique($withs));
        }

        /*
         * Apply search term
         */
        $query->where(function ($innerQuery) use ($primarySearchable, $relationSearchable, $joins) {

            /*
             * Search primary columns
             */
            if (count($primarySearchable) > 0) {
                $this->applySearchToQuery($innerQuery, $primarySearchable, 'or');
            }

            /*
             * Search relation columns
             */
            if ($joins) {
                foreach (array_unique($joins) as $join) {
                    /*
                     * Apply a supplied search term for relation columns and
                     * constrain the query only if there is something to search for
                     */
                    $columnsToSearch = array_get($relationSearchable, $join, []);

                    if (count($columnsToSearch) > 0) {
                        $innerQuery->orWhereHas($join, function ($_query) use ($columnsToSearch) {
                            $this->applySearchToQuery($_query, $columnsToSearch);
                        });
                    }
                }
            }
        });

        /*
         * Custom select queries
         */
        foreach ($this->getVisibleColumns() as $column) {
            if (!isset($column->sqlSelect)) {
                continue;
            }

            $alias = $query->getQuery()->getGrammar()->wrap($column->columnName);

            /*
             * Relation column
             */
            if (isset($column->relation)) {
                // @todo Find a way...
                $relationType = $this->model->getRelationType($column->relation);
                if ($relationType == 'morphTo') {
                    throw new ApplicationException('The relationship morphTo is not supported for calendar columns.');
                }

                $table =  $this->model->makeRelation($column->relation)->getTable();
                $sqlSelect = $this->parseTableName($column->sqlSelect, $table);

                /*
                 * Manipulate a count query for the sub query
                 */
                $relationObj = $this->model->{$column->relation}();
                $countQuery = $relationObj->getRelationExistenceQuery($relationObj->getRelated()->newQueryWithoutScopes(), $query);

                $joinSql = $this->isColumnRelated($column, true)
                ? DbDongle::raw("group_concat(" . $sqlSelect . " separator ', ')")
                : DbDongle::raw($sqlSelect);

                $joinQuery = $countQuery->select($joinSql);

                if (!empty($column->config['conditions'])) {
                    $joinQuery->whereRaw(DbDongle::parse($column->config['conditions']));
                }

                $joinSql = $joinQuery->toSql();

                $selects[] = Db::raw("(".$joinSql.") as ".$alias);

                /*
                 * If this is a polymorphic relation there will be bindings that need to be added to the query
                 */
                $bindings = array_merge($bindings, $countQuery->getBindings());
            }
            /*
             * Primary column
             */
            else {
                $sqlSelect = $this->parseTableName($column->sqlSelect, $primaryTable);
                $selects[] = DbDongle::raw($sqlSelect . ' as '. $alias);
            }
        }

        /*
         * Apply filters
         */
        // Extra object parameters
        $userObj    = BackendAuth::user();
        $user       = $userObj->id;
        $groupsObjs = array();
        foreach ($userObj->groups as $group)
            array_push($groupsObjs, $group->id);
        $groups = implode(',', $groupsObjs);

        // Standard application of Filters to query
        foreach ($this->filterCallbacks as &$callback) {
            // $callback is [Filter object, method]
            // Apply custom replacements :user and :groups
            $filter = &$callback[0];
            // => defineFilterScopes() otherwise it will happen later and overwrite the changes
            $filter->prepareVars();
            if ($filter instanceof Backend\Widgets\Filter) {
                foreach ($filter->getScopes() as &$scope) {
                    if ($scope->conditions) {
                        $scope->conditions = str_replace(':user',   $user,   $scope->conditions);
                        $scope->conditions = str_replace(':groups', $groups, $scope->conditions);
                        //print("$scope->conditions<hr/>");
                    }
                }
            }

            // Standard application of Filters to query
            // $callback is Filter::applyAllScopesToQuery($query)
            $callback($query);
        }

        /*
         * Add custom selects
         */
        $query->addSelect($selects);

        /*
         * Add bindings for polymorphic relations
         */
        $query->addBinding($bindings, 'select');

        $query->orderBy('date');

        /**
         * @event backend.calendar.extendQuery
         * Provides an opportunity to modify and / or return the `$query` object after the Calendar widget has applied its scopes to it and before it's used to get the records.
         *
         * Example usage:
         *
         *     Event::calendaren('backend.calendar.extendQuery', function ($calendarWidget, $query) {
         *         $newQuery = MyModel::newQuery();
         *         return $newQuery;
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.extendQuery', function ($query) {
         *         $query->whereNull('deleted_at');
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('backend.calendar.extendQuery', [$query])) {
            return $event;
        }

        return $query;
    }

    public function prepareModel()
    {
        traceLog('Method ' . __METHOD__ . '() has been deprecated, please use the ' . __CLASS__ . '::prepareQuery() method instead.');
        return $this->prepareQuery();
    }

    /**
     * Returns all the records from the supplied model, after filtering.
     * @return Collection
     */
    protected function getRecords()
    {
        // Set default date range
        // TODO: Combine this with the front end default setting
        // should always have a value?
        $filter  = &$this->filterCallbacks[0][0];
        // Ensure that $filter->allScopes is defined. Use:
        //   onFilterUpdate() => defineFilterScopes()
        //   => addScopes() => makeFilterScope() => getScopeValue() => getSession()
        // $filter->onFilterUpdate();
        $filter->addScopes($filter->scopes);
        $current = $filter->getScopeValue('date');
        $today   = (new Carbon())->setHours(0)->setMinutes(0)->setSeconds(0)->setMillis(0);
        if (!$current) {
            // Default to today and onward +1 month
            $filter->setScopeValue('date', [
                $today,
                (clone $today)->addMonth()->addMonth() // 2, to make sure we exceed the initial display size
            ]);
        }

        $query = $this->prepareQuery();

        $records = $query->get();

        /**
         * @event backend.calendar.extendRecords
         * Provides an opportunity to modify and / or return the `$records` Collection object before the widget uses it.
         *
         * Example usage:
         *
         *     Event::calendaren('backend.calendar.extendRecords', function ($calendarWidget, $records) {
         *         $model = MyModel::where('always_include', true)->first();
         *         $records->prepend($model);
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.extendRecords', function ($records) {
         *         $model = MyModel::where('always_include', true)->first();
         *         $records->prepend($model);
         *     });
         *
         */
        if ($event = $this->fireSystemEvent('backend.calendar.extendRecords', [&$records])) {
            $records = $event;
        }

        // TODO: This read restriction would be more efficient in the SQL
        $this->records = new \Winter\Storm\Database\Collection();
        foreach ($records as &$record)
            if ($record->canRead())
                $this->records->add($record);

        return $this->records;
    }

    protected function organiseRecords()
    {
        // Prepare weeks
        // DateRange default / filter values
        $dateToday       = Backend::makeCarbon('today');
        $filter          = &$this->filterCallbacks[0][0];
        $filterDateRange = $filter->getScopeValue('date');
        $pager_start     = $filterDateRange[0]->setHours(0)->setMinutes(0)->setSeconds(0)->setMillis(0);
        $pager_end       = $filterDateRange[1]->setHours(0)->setMinutes(0)->setSeconds(0)->setMillis(0);

        $this->weeks     = array();
        $date_current    = clone $pager_start;
        $dow             = $pager_start->format('w');
        $date_current->sub(new \DateInterval("P${dow}D")); // Start at beginning of the week
        $w = 0;

        if ($this->records instanceof \Winter\Storm\Database\Collection) {
            $instances = $this->records->all();
            $instance  = current($instances);

            // Simple Debug
            if (isset($_GET['debug']) && TRUE) {
                print('<h1>instances</h1><ul>');
                foreach ($instances as $instance) {
                    $eventPart = $instance->eventPart;
                    $event     = $eventPart->event;
                    $date      = $instance->date->format('<b>Y-m</b>-d');
                    print("<li>$event->id/$eventPart->id/$instance->id: $date</li>");
                }
                print('</ul>');
                die();
            }

            // Advance to pager begin
            while ($instance && $instance->date < $date_current) $instance = next($instances);

            do {
                $week         = array('date' => clone $date_current);
                $month1stWeek = false;

                for ($dow = 0; $dow < 7; $dow++) { // 7 days
                    $day = array(
                        'date'    => clone $date_current,
                        'range'   => ($date_current >= $pager_start && $date_current <= $pager_end ? 'in' : 'out'),
                        'type'    => 'normal',
                        'format'  => 'd',
                        'title'   => '',
                        'period'  => '',
                        'classes' => array(),
                        'styles'  => array(),
                        'events'  => array(),
                    );

                    if      ($date_current < $dateToday) $day['period'] = 'past';
                    else if ($date_current > $dateToday) $day['period'] = 'future';
                    else                                 $day['period'] = 'today';

                    // Month Start
                    $m = $date_current->format('m'); // Month number
                    $d = $date_current->format('d'); // Day in month
                    if (!isset($m_old) || $m_old != $m) {
                        $m_old          = $m;
                        $day['format'] .= ', M*'; // M* means translated month name
                        $day['type']    = 'month-start';
                        if ($d == 1) $month1stWeek   = true;
                    }
                    $day['classes'] = array("$day[range]-range", "day-type-$day[type]", "time-$day[period]");
                    if ($month1stWeek) array_push($day['classes'], 'month-1st-week');

                    // Add records in to the days from $this->records;
                    if ($instance) {
                        do {
                            $sameday = ($instance->date == $date_current);
                            if ($sameday) {
                                $eventPart = $instance->eventPart;
                                if ($eventPart->type->whole_day) {
                                    $eventName  = e($eventPart->name ? $eventPart->name : '<no name>');
                                    $className  = ($eventPart->name ? 'whole-day-' . preg_replace('/[^a-z0-9]/', '-', strtolower($eventPart->name)) : NULL);
                                    $comma      = ($day['title'] ? ', ' : '');
                                    $bubbleHelp = $instance->bubbleHelp();

                                    // Cut-off near last word
                                    $eventNameFormat = $eventName;
                                    if (strlen($eventName) > 16) {
                                        $eventNameFormat = substr($eventName, 0, 16);
                                        $eventNameFormat = preg_replace('/ +[^ ]{0,8}$/', '', $eventNameFormat);
                                    }

                                    if ($className) array_push($day['classes'], $className);
                                    $day['title'] .= "$comma<a href='#'
                                        data-handler='onOpenEvent'
                                        data-request-data='path:\"$instance->id\"'
                                        data-request-complete='event.stopPropagation();'
                                        data-control='popup'
                                        title='$bubbleHelp'
                                    >$eventNameFormat</a>";
                                    array_push($day['styles'], $eventPart->type->style);
                                } else {
                                    array_push($day['events'], $instance);
                                }
                                $instance = next($instances);
                            }
                        } while ($sameday && $instance);
                    }
                    $date_current->add(new \DateInterval('P1D'));
                    array_push($week, $day);
                }
                array_push($this->weeks, $week);
            } while ($date_current < $pager_end);
        } else {
            throw new ApplicationException('Records type not supported');
        }

        $this->vars['weeks'] = $this->weeks;
    }

    /**
     * Get all the registered columns for the instance.
     * @return array
     */
    public function getColumns()
    {
        return $this->allColumns ?: $this->defineCalendarCells();
    }

    /**
     * Get a specified column object
     * @param  string $column
     * @return mixed
     */
    public function getColumn($column)
    {
        if (!isset($this->allColumns[$column])) {
            throw new ApplicationException('No definition for column ' . $column);
        }

        return $this->allColumns[$column];
    }

    /**
     * Returns the calendar columns that are visible by calendar settings or default
     */
    public function getVisibleColumns()
    {
        $definitions = $this->defineCalendarCells();
        $columns = [];

        /*
         * Supplied column calendar
         */
        if ($this->showSetup && $this->columnOverride === null) {
            $this->columnOverride = $this->getUserPreference('visible', null);
        }

        if ($this->columnOverride && is_array($this->columnOverride)) {
            $invalidColumns = array_diff($this->columnOverride, array_keys($definitions));
            if (!count($definitions)) {
                throw new ApplicationException(Lang::get(
                    'backend::lang.calendar.missing_column',
                    ['columns'=>implode(',', $invalidColumns)]
                ));
            }

            $availableColumns = array_intersect($this->columnOverride, array_keys($definitions));
            foreach ($availableColumns as $columnName) {
                $definitions[$columnName]->invisible = false;
                $columns[$columnName] = $definitions[$columnName];
            }
        }
        /*
         * Use default column calendar
         */
        else {
            foreach ($definitions as $columnName => $column) {
                if ($column->invisible) {
                    continue;
                }

                $columns[$columnName] = $definitions[$columnName];
            }
        }

        // TODO: Configurable search columns
        // TODO: Can this be done with eventPart[name]?
        $event = new CalendarCell('name', 'Name');
        $event->relation = 'eventPart';
        $event->valueFrom = 'name';
        $columns[] = $event;

        return $this->visibleColumns = $columns;
    }

    /**
     * Builds an array of calendar columns with keys as the column name and values as a CalendarCell object.
     */
    protected function defineCalendarCells()
    {
        if (!isset($this->columns) || !is_array($this->columns) || !count($this->columns)) {
            $class = get_class($this->model instanceof Model ? $this->model : $this->controller);
            throw new ApplicationException(Lang::get('backend::lang.calendar.missing_columns', compact('class')));
        }

        $this->addColumns($this->columns);

        /**
         * @event backend.calendar.extendColumns
         * Provides an opportunity to modify the columns of a Calendar widget
         *
         * Example usage:
         *
         *     Event::calendaren('backend.calendar.extendColumns', function ($calendarWidget) {
         *         // Only for the User controller
         *         if (!$calendarWidget->getController() instanceof \Backend\Controllers\Users) {
         *             return;
         *         }
         *
         *         // Only for the User model
         *         if (!$calendarWidget->model instanceof \Acorn\User\Models\User) {
         *             return;
         *         }
         *
         *         // Add an extra birthday column
         *         $calendarWidget->addColumns([
         *             'birthday' => [
         *                 'label' => 'Birthday'
         *             ]
         *         ]);
         *
         *         // Remove a Surname column
         *         $calendarWidget->removeColumn('surname');
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.extendColumns', function () use ($calendarWidget) {
         *         // Only for the User controller
         *         if (!$calendarWidget->getController() instanceof \Backend\Controllers\Users) {
         *             return;
         *         }
         *
         *         // Only for the User model
         *         if (!$calendarWidget->model instanceof \Acorn\User\Models\User) {
         *             return;
         *         }
         *
         *         // Add an extra birthday column
         *         $calendarWidget->addColumns([
         *             'birthday' => [
         *                 'label' => 'Birthday'
         *             ]
         *         ]);
         *
         *         // Remove a Surname column
         *         $calendarWidget->removeColumn('surname');
         *     });
         *
         */
        $this->fireSystemEvent('backend.calendar.extendColumns');

        /*
         * Use a supplied column order
         */
        if ($columnOrder = $this->getUserPreference('order', null)) {
            $orderedDefinitions = [];
            foreach ($columnOrder as $column) {
                if (isset($this->allColumns[$column])) {
                    $orderedDefinitions[$column] = $this->allColumns[$column];
                }
            }

            $this->allColumns = array_merge($orderedDefinitions, $this->allColumns);
        }

        return $this->allColumns;
    }

    /**
     * Programatically add columns, used internally and for extensibility.
     * @param array $columns Column definitions
     */
    public function addColumns(array $columns)
    {
        /*
         * Build a final collection of calendar column objects
         */
        foreach ($columns as $columnName => $config) {
            // Check if user has permissions to show this column
            $permissions = array_get($config, 'permissions');
            if (!empty($permissions) && !BackendAuth::getUser()->hasAccess($permissions, false)) {
                continue;
            }

            $this->allColumns[$columnName] = $this->makeCalendarCell($columnName, $config);
        }
    }

    /**
     * Programatically remove a column, used for extensibility.
     * @param string $column Column name
     */
    public function removeColumn($columnName)
    {
        if (isset($this->allColumns[$columnName])) {
            unset($this->allColumns[$columnName]);
        }
    }

    /**
     * Creates a calendar column object from it's name and configuration.
     * This is a copy of the ListView (extends WidgetBase) method
     */
    protected function makeCalendarCell($name, $config)
    {
        if (is_string($config)) {
            $label = $config;
        }
        elseif (isset($config['label'])) {
            $label = $config['label'];
        }
        else {
            $label = studly_case($name);
        }

        /*
         * Auto configure pivot relation
         */
        if (starts_with($name, 'pivot[') && strpos($name, ']') !== false) {
            $_name = HtmlHelper::nameToArray($name);
            $relationName = array_shift($_name);
            $valueFrom = array_shift($_name);

            if (count($_name) > 0) {
                $valueFrom  .= '['.implode('][', $_name).']';
            }

            $config['relation'] = $relationName;
            $config['valueFrom'] = $valueFrom;
            $config['searchable'] = false;
        }
        /*
         * Auto configure standard relation
         */
        elseif (strpos($name, '[') !== false && strpos($name, ']') !== false) {
            $config['valueFrom'] = $name;
            $config['sortable'] = false;
            $config['searchable'] = false;
        }

        $columnType = $config['type'] ?? null;

        $column = new CalendarCell($name, $label);
        $column->displayAs($columnType, $config);

        return $column;
    }

    /**
     * Calculates the total columns used in the calendar, including checkboxes
     * and other additions.
     */
    protected function getTotalColumns()
    {
        $columns = $this->visibleColumns ?: $this->getVisibleColumns();
        $total = count($columns);

        if ($this->showCheckboxes) {
            $total++;
        }

        if ($this->showSetup) {
            $total++;
        }

        return $total;
    }

    /**
     * Looks up the column header
     */
    public function getHeaderValue($column)
    {
        $value = Lang::get($column->label);

        /**
         * @event backend.calendar.overrideHeaderValue
         * Overrides the column header value in a calendar widget.
         *
         * If a value is returned from this event, it will be used as the value for the provided column.
         * `$value` is passed by reference so modifying the variable in place is also supported. Example usage:
         *
         *     Event::calendaren('backend.calendar.overrideHeaderValue', function ($calendarWidget, $column, &$value) {
         *         $value .= '-modified';
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.overrideHeaderValue', function ($column, $value) {
         *         return 'Custom header value';
         *     });
         *
         */
        if ($response = $this->fireSystemEvent('backend.calendar.overrideHeaderValue', [$column, &$value])) {
            $value = $response;
        }

        return $value;
    }

    /**
     * Returns a raw column value
     * @return string
     */
    public function getColumnValueRaw($record, $column)
    {
        $columnName = $column->columnName;

        /*
         * Handle taking value from model relation.
         */
        if ($column->valueFrom && $column->relation) {
            $columnName = $column->relation;

            if (!array_key_exists($columnName, $record->getRelations())) {
                $value = null;
            }
            elseif ($this->isColumnRelated($column, true)) {
                $value = $record->{$columnName}->calendars($column->valueFrom);
            }
            elseif ($this->isColumnRelated($column) || $this->isColumnPivot($column)) {
                $value = $record->{$columnName}
                ? $column->getValueFromData($record->{$columnName})
                : null;
            }
            else {
                $value = null;
            }
        }
        /*
         * Handle taking value from model attribute.
         */
        elseif ($column->valueFrom) {
            $value = $column->getValueFromData($record);
        }
        /*
         * Otherwise, if the column is a relation, it will be a custom select,
         * so prevent the Model from attempting to load the relation
         * if the value is NULL.
         */
        else {
            if ($record->hasRelation($columnName) && array_key_exists($columnName, $record->attributes)) {
                $value = $record->attributes[$columnName];
                // Load the value from the relationship counter if useRelationCount is specified
            } elseif ($column->relation && ($column->config['useRelationCount'] ?? false)) {
                $relation = Str::snake($column->relation);
                $value = $record->{"{$relation}_count"};
            } else {
                $value = $record->{$columnName};
            }
        }

        /**
         * @event backend.calendar.overrideColumnValueRaw
         * Overrides the raw column value in a calendar widget.
         *
         * If a value is returned from this event, it will be used as the raw value for the provided column.
         * `$value` is passed by reference so modifying the variable in place is also supported. Example usage:
         *
         *     Event::calendaren('backend.calendar.overrideColumnValueRaw', function ($calendarWidget, $record, $column, &$value) {
         *         $value .= '-modified';
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.overrideColumnValueRaw', function ($record, $column, $value) {
         *         return 'No values for you!';
         *     });
         *
         */
        if ($response = $this->fireSystemEvent('backend.calendar.overrideColumnValueRaw', [$record, $column, &$value])) {
            $value = $response;
        }

        return $value;
    }

    /**
     * Returns a column value, with filters applied
     * @return string
     */
    public function getColumnValue($record, $column)
    {
        $value = $this->getColumnValueRaw($record, $column);

        if (method_exists($this, 'eval'. studly_case($column->type) .'TypeValue')) {
            $value = $this->{'eval'. studly_case($column->type) .'TypeValue'}($record, $column, $value);
        }
        else {
            $value = $this->evalCustomCalendarType($column->type, $record, $column, $value);
        }

        /*
         * Apply default value.
         */
        if (($value === '' || is_null($value)) && !empty($column->defaults)) {
            $value = Lang::get($column->defaults);
        }

        /**
         * @event backend.calendar.overrideColumnValue
         * Overrides the column value in a calendar widget.
         *
         * If a value is returned from this event, it will be used as the value for the provided column.
         * `$value` is passed by reference so modifying the variable in place is also supported. Example usage:
         *
         *     Event::calendaren('backend.calendar.overrideColumnValue', function ($calendarWidget, $record, $column, &$value) {
         *         $value .= '-modified';
         *     });
         *
         * Or
         *
         *     $calendarWidget->bindEvent('calendar.overrideColumnValue', function ($record, $column, $value) {
         *         return 'No values for you!';
         *     });
         *
         */
        if ($response = $this->fireSystemEvent('backend.calendar.overrideColumnValue', [$record, $column, &$value])) {
            $value = $response;
        }

        return $value;
    }

    //
    // Value processing
    //

    /**
     * Process a custom calendar types registered by plugins.
     */
    protected function evalCustomCalendarType($type, $record, $column, $value)
    {
        $plugins = PluginManager::instance()->getRegistrationMethodValues('registerCalendarCellTypes');

        foreach ($plugins as $availableTypes) {
            if (!isset($availableTypes[$type])) {
                continue;
            }

            $callback = $availableTypes[$type];

            if (is_callable($callback)) {
                return call_user_func_array($callback, [$value, $column, $record]);
            }
        }

        $customMessage = '';
        if ($type === 'relation') {
            $customMessage = 'Type: relation is not supported, instead use the relation property to specify a relationship to pull the value from and set the type to the type of the value expected.';
        }

        throw new ApplicationException(sprintf('Calendar column type "%s" could not be found. %s', $type, $customMessage));
    }

    /**
     * Process as text, escape the value
     * @return string
     */
    protected function evalTextTypeValue($record, $column, $value)
    {
        if (is_array($value) && count($value) == count($value, COUNT_RECURSIVE)) {
            $value = implode(', ', $value);
        }

        if (is_string($column->format) && !empty($column->format)) {
            $value = sprintf($column->format, $value);
        }

        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * Process an image value
     * @return string
     */
    protected function evalImageTypeValue($record, $column, $value)
    {
        $image = null;
        $config = $column->config;

        // Get config options with defaults
        $width = isset($config['width']) ? $config['width'] : 50;
        $height = isset($config['height']) ? $config['height'] : 50;
        $options = isset($config['options']) ? $config['options'] : [];
        $fallback = isset($config['default']) ? $config['default'] : null;

        // Handle attachMany relationships
        if (isset($record->attachMany[$column->columnName])) {
            $image = $value->first();

            // Handle attachOne relationships
        } elseif (isset($record->attachOne[$column->columnName])) {
            $image = $value;

            // Handle absolute URLs
        } elseif (str_contains($value, '://')) {
            $image = $value;

            // Handle embedded data URLs
        } elseif (starts_with($value, 'data:image')) {
            $image = $value;

            // Assume all other values to be from the media library
        } elseif (!empty($value)) {
            $image = MediaLibrary::url($value);
        }

        if (!$image && $fallback) {
            $image = $fallback;
        }

        if ($image) {
            $imageUrl = ImageResizer::filterGetUrl($image, $width, $height, $options);
            return "<img src='$imageUrl' width='$width' height='$height' />";
        }
    }

    /**
     * Process as number, proxy to text
     * @return string
     */
    protected function evalNumberTypeValue($record, $column, $value)
    {
        return $this->evalTextTypeValue($record, $column, $value);
    }

    /**
     * Process as partial reference
     */
    protected function evalPartialTypeValue($record, $column, $value)
    {
        return $this->controller->makePartial($column->path ?: $column->columnName, [
        'calendarColumn' => $column,
        'calendarRecord' => $record,
        'calendarValue'  => $value,
        'column'     => $column,
        'record'     => $record,
        'value'      => $value
        ]);
    }

    /**
     * Process as boolean switch
     */
    protected function evalSwitchTypeValue($record, $column, $value)
    {
        $contents = '';

        if ($value) {
            $contents = Lang::get('backend::lang.calendar.column_switch_true');
        }
        else {
            $contents = Lang::get('backend::lang.calendar.column_switch_false');
        }

        return $contents;
    }

    /**
     * Process as a datetime value
     */
    protected function evalDatetimeTypeValue($record, $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        if ($column->format !== null) {
            $value = $dateTime->format($column->format);
        }
        else {
            $value = $dateTime->toDayDateTimeString();
        }

        $options = [
        'defaultValue' => $value,
        'format' => $column->format,
        'formatAlias' => 'dateTimeLongMin'
        ];

        if (!empty($column->config['ignoreTimezone'])) {
            $options['ignoreTimezone'] = true;
        }

        return Backend::dateTime($dateTime, $options);
    }

    /**
     * Process as a time value
     */
    protected function evalTimeTypeValue($record, $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $format = $column->format ?? 'g:i A';

        $value = $dateTime->format($format);

        $options = [
        'defaultValue' => $value,
        'format' => $column->format,
        'formatAlias' => 'time'
        ];

        if (!empty($column->config['ignoreTimezone'])) {
            $options['ignoreTimezone'] = true;
        }

        return Backend::dateTime($dateTime, $options);
    }

    /**
     * Process as a date value
     */
    protected function evalDateTypeValue($record, $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        if ($column->format !== null) {
            $value = $dateTime->format($column->format);
        }
        else {
            $value = $dateTime->toFormattedDateString();
        }

        $options = [
        'defaultValue' => $value,
        'format' => $column->format,
        'formatAlias' => 'dateLongMin'
        ];

        if (!empty($column->config['ignoreTimezone'])) {
            $options['ignoreTimezone'] = true;
        }

        return Backend::dateTime($dateTime, $options);
    }

    /**
     * Process as diff for humans (1 min ago)
     */
    protected function evalTimesinceTypeValue($record, $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $value = DateTimeHelper::timeSince($dateTime);

        $options = [
        'defaultValue' => $value,
        'timeSince' => true
        ];

        if (!empty($column->config['ignoreTimezone'])) {
            $options['ignoreTimezone'] = true;
        }

        return Backend::dateTime($dateTime, $options);
    }

    /**
     * Process as time as current tense (Today at 0:00)
     */
    protected function evalTimetenseTypeValue($record, $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $this->validateDateTimeValue($value, $column);

        $value = DateTimeHelper::timeTense($dateTime);

        $options = [
        'defaultValue' => $value,
        'timeTense' => true
        ];

        if (!empty($column->config['ignoreTimezone'])) {
            $options['ignoreTimezone'] = true;
        }

        return Backend::dateTime($dateTime, $options);
    }
    /**
     * Process as background color, to be seen at calendar
     */
    protected function evalColorPickerTypeValue($record, $column, $value)
    {
        return  '<span style="width:30px; height:30px; display:inline-block; background:'.e($value).'; padding:10px"><span>';
    }
    /**
     * Validates a column type as a date
     */
    protected function validateDateTimeValue($value, $column)
    {
        $value = DateTimeHelper::makeCarbon($value, false);

        if (!$value instanceof Carbon) {
            throw new ApplicationException(Lang::get(
                'backend::lang.calendar.invalid_column_datetime',
                ['column' => $column->columnName]
            ));
        }

        return $value;
    }

    //
    // Filtering
    //

    public function addFilter(callable $filter)
    {
        $this->filterCallbacks[] = $filter;
    }

    //
    // Searching
    //

    /**
     * Applies a search term to the calendar results, searching will disable tree
     * view if a value is supplied.
     * @param string $term
     * @param boolean $resetPagination
     */
    public function setSearchTerm($term)
    {
        $this->searchTerm = $term;
    }

    /**
     * Applies a search options to the calendar search.
     * @param array $options
     */
    public function setSearchOptions($options = [])
    {
        extract(array_merge([
            'mode' => null,
            'scope' => null
        ], $options));

        $this->searchMode = $mode;
        $this->searchScope = $scope;
    }

    /**
     * Returns a collection of columns which can be searched.
     * @return array
     */
    protected function getSearchableColumns()
    {
        $columns = $this->getColumns();
        $searchable = [];

        foreach ($columns as $column) {
            if (!$column->searchable) {
                continue;
            }

            $searchable[] = $column;
        }

        return $searchable;
    }

    /**
     * Applies the search constraint to a query.
     */
    protected function applySearchToQuery($query, $columns, $boolean = 'and')
    {
        $term = $this->searchTerm;

        if ($scopeMethod = $this->searchScope) {
            $searchMethod = $boolean == 'and' ? 'where' : 'orWhere';
            $query->$searchMethod(function ($q) use ($term, $columns, $scopeMethod) {
                $q->$scopeMethod($term, $columns);
            });
        }
        else {
            $searchMethod = $boolean == 'and' ? 'searchWhere' : 'orSearchWhere';
            $query->$searchMethod($term, $columns, $this->searchMode);
        }
    }

    static public function ordinal($number) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13))
            $ordinal = 'th';
        else
            $ordinal = $ends[$number % 10];
        return "$number$ordinal";
    }


    //
    // Calendar Setup
    //

    /**
     * Event handler to display the calendar set up.
     */
    public function onLoadSetup()
    {
        $this->vars['columns'] = $this->getSetupCalendarCells();
        $this->vars['perPageOptions'] = $this->getSetupPerPageOptions();
        $this->vars['recordsPerPage'] = $this->recordsPerPage;
        return $this->makePartial('setup_form');
    }

    /**
     * Event handler to apply the calendar set up.
     */
    public function onApplySetup()
    {
        if (($visibleColumns = post('visible_columns')) && is_array($visibleColumns)) {
            $this->columnOverride = $visibleColumns;
            $this->putUserPreference('visible', $this->columnOverride);
        }

        $this->recordsPerPage = post('records_per_page', $this->recordsPerPage);
        $this->putUserPreference('order', post('column_order'));
        $this->putUserPreference('per_page', $this->recordsPerPage);
        return $this->onRefresh();
    }

    /**
     * Event handler to apply the calendar set up.
     */
    public function onResetSetup()
    {
        $this->clearUserPreference('order');
        $this->clearUserPreference('visible');
        $this->clearUserPreference('per_page');
        return $this->onRefresh();
    }

    /**
     * Returns an array of allowable records per page.
     */
    protected function getSetupPerPageOptions()
    {
        $perPageOptions = is_array($this->perPageOptions) ? $this->perPageOptions : [20, 40, 80, 100, 120];
        if (!in_array($this->recordsPerPage, $perPageOptions)) {
            $perPageOptions[] = $this->recordsPerPage;
        }

        sort($perPageOptions);
        return $perPageOptions;
    }

    /**
     * Returns all the calendar columns used for calendar set up.
     */
    protected function getSetupCalendarCells()
    {
        /*
         * Force all columns invisible
         */
        $columns = $this->defineCalendarCells();
        foreach ($columns as $column) {
            $column->invisible = true;
        }

        return array_merge($columns, $this->getVisibleColumns());
    }


    //
    // Helpers
    //

    /**
     * Check if column refers to a relation of the model
     * @param  CalendarCell  $column Calendar column object
     * @param  boolean     $multi  If set, returns true only if the relation is a "multiple relation type"
     * @return boolean
     */
    protected function isColumnRelated($column, $multi = false)
    {
        if (!isset($column->relation) || $this->isColumnPivot($column)) {
            return false;
        }

        if (!$this->model->hasRelation($column->relation)) {
            throw new ApplicationException(Lang::get(
                'backend::lang.model.missing_relation',
                ['class'=>get_class($this->model), 'relation'=>$column->relation]
            ));
        }

        if (!$multi) {
            return true;
        }

        $relationType = $this->model->getRelationType($column->relation);

        return in_array($relationType, [
            'hasMany',
            'belongsToMany',
            'morphToMany',
            'morphedByMany',
            'morphMany',
            'attachMany',
            'hasManyThrough'
        ]);
    }

    /**
     * Checks if a column refers to a pivot model specifically.
     * @param  CalendarCell  $column Calendar column object
     * @return boolean
     */
    protected function isColumnPivot($column)
    {
        if (!isset($column->relation) || $column->relation != 'pivot') {
            return false;
        }

        return true;
    }

    public function syncFiles(Calendar $calendar)
    {
        $message = NULL;

        // Write external foreign ICS calendar files with new data
        if ($syncFile = $calendar->sync_file) {

            // TODO: Write ICS calendar header and timezones
            $default_time_zone = Settings::get('default_time_zone');

            switch ($calendar->sync_format) {
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
                    $events = &$calendar->events;
                    foreach ($events as $event) {
                        foreach ($event->event_parts as $part) {
                            foreach ($part->instances as $instance) {
                                $output .= $instance->format($calendar->sync_format);
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
                    $writtenTo  = trans('events written to');
                    $message    = "$eventCount $writtenTo $location (ICS)";
                    break;
            }
        }

        return $message;
    }

    /**
     * AJAX Event handlers
     */
    public function onClose()
    {
        $result    = 'success';
        $post      = post();
        $eventPart = EventPart::find($post['templatePath']);
        $eventPart->save(); // Will unlock

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onCreateEvent()
    {
        $post      = post();
        $event     = new Event();
        $eventPart = new EventPart();

        $result = 'error';
        try {
            $event->fill($post['event']);
            $event->save();

            $eventPart->fill($post);
            $eventPart->event_id = $event->id;
            $eventPart->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event created $message");
        } catch (Exception $ex) {
            Flash::error('Event not created: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onUpdateEventInstanceOnly()
    {
        $post       = post();
        // TODO: Move templatePath to instanceID, not eventPart
        $instance   = Instance::find($post['instanceID']);
        $eventPart  = $instance->eventPart;
        $event      = $eventPart->event;
        $postStart  = new \DateTime($post['start']);
        $postEnd    = new \DateTime($post['end']);
        $type       = 'instance';

        $result = 'error';
        try {
            if ($eventPart->repeat) {
                // Repeating events
                // Remove the instance
                $instances_deleted = $eventPart->instances_deleted;
                array_push($instances_deleted, $instance->instance_num);
                $eventPart->instances_deleted = $instances_deleted; // Direct attribute modification
                $eventPart->checkDirtyWrite($post); // throw DirtyWrite
                // These may throw AuthorizationException, ObjectIsLocked
                $eventPart->save();

                // New event part for the breakaway instance
                // Has the User changed the start date?
                // TODO: What if she just changed the times?
                // TODO: What if the user wants to move this instance *to* the event start date
                $eventPart2      = new EventPart();
                $eventStartDirty = ($eventPart->start != $postStart);
                $eventEndDirty   = ($eventPart->end   != $postEnd);
                $eventPart2->fill($post);
                $eventPart2->event_id = $eventPart->event_id;
                $eventPart2->repeat   = NULL;
                $eventPart2->until    = NULL;
                $eventPart2->start    = ($eventStartDirty ? $postStart : $instance->instance_start);
                $eventPart2->end      = ($eventEndDirty   ? $postEnd   : $instance->instance_end);
                $eventPart2->save();
            } else {
                // Non-Repeating events
                $type = '';
                $eventPart->fill($post);
                $eventPart->save();
            }

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event $type updated $message");
        } catch (Exception $ex) {
            Flash::error("Event $type not updated: " . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onUpdateEventWholeSeries()
    {
        $result    = 'error';
        $post      = post();
        $postEvent = $post['event'];
        $eventPart = EventPart::find($post['templatePath']);
        $event     = $eventPart->event;

        try {
            // These may throw DirtyWrite
            $eventPart->fill($post);
            $event->fill($postEvent);

            // These may throw AuthorizationException, ObjectIsLocked
            $event->save();
            $eventPart->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event updated $message");
        } catch (Exception $ex) {
            // AuthorizationException (permissions), DirtyWrite, ObjectIsLocked
            Flash::error('Event not updated: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onUpdateEventFromInstance()
    {
        $post       = post();
        $instance   = Instance::find($post['instanceID']);
        $eventPart1 = $instance->eventPart;
        $event      = $eventPart1->event;
        $postStart  = new \DateTime($post['start']);
        $startDateDirty = ($eventPart1->start != $postStart);
        $postEnd    = new \DateTime($post['end']);
        $endDateDirty = ($eventPart1->end != $postEnd);
        // $period    = $eventPart1->end->diff($eventPart1->start);
        $eventPart2 = new EventPart();

        $result = 'error';
        try {
            // End original part at the instance selected
            $eventPart1->until = $instance->instance_start;
            // These may throw AuthorizationException, ObjectIsLocked
            $eventPart1->save();

            // Create a new part with the new details
            // starting from the instance selected
            // unless the dates are dirty
            // TODO: Rollback previous save onException?
            // These may throw DirtyWrite
            $eventPart2->fill($post);
            $eventPart2->event_id = $eventPart1->event_id;
            $eventPart2->start    = ($startDateDirty ? $postStart : $instance->instance_start);
            $eventPart2->end      = ($endDateDirty   ? $postEnd   : $instance->instance_end);
            // These may throw AuthorizationException, ObjectIsLocked
            $eventPart2->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event updated $message");
        } catch (Exception $ex) {
            Flash::error('Event not updated: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onDeleteEventAfter()
    {
        $post      = post();
        $instance  = Instance::find($post['instanceID']);
        $eventPart = $instance->eventPart;
        $event     = $eventPart->event;

        $result = 'error';
        try {
            $eventPart->until = $instance->instance_start;
            // These may throw AuthorizationException, ObjectIsLocked
            $eventPart->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            $deleted_from = $instance->instance_start->format('Y-m-d');
            Flash::success("Event deleted from $deleted_from $message");
        } catch (Exception $ex) {
            Flash::error('Event not deleted: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onDeleteEventInstanceOnly()
    {
        $post      = post();
        $instance  = Instance::find($post['instanceID']);
        $eventPart = $instance->eventPart;
        $event     = $eventPart->event;
        $instances_deleted = (array) $eventPart->instances_deleted;
        array_push($instances_deleted, $instance->instance_num);

        $result = 'error';
        try {
            $eventPart->instances_deleted = $instances_deleted; // Direct attribute modification
            // These may throw AuthorizationException, ObjectIsLocked
            $eventPart->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event instance deleted $message");
        } catch (Exception $ex) {
            Flash::error('Event instance not deleted: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onDeleteEventWholeSeries()
    {
        $post      = post();
        $eventPart = EventPart::find($post['templatePath']);
        $event     = $eventPart->event;

        $result = 'error';
        try {
            // TODO: WebSockets(), permissions, locking, etc.
            $eventPart->event->delete(); // Cascade

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Event deleted $message");
        } catch (Exception $ex) {
            Flash::error('Event not deleted: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onReInstateDeletedInstances()
    {
        $post      = post();
        $eventPart = EventPart::find($post['templatePath']);
        $event     = $eventPart->event;

        $result = 'error';
        try {
            $eventPart->instances_deleted = NULL;
            // These may throw AuthorizationException, ObjectIsLocked
            $eventPart->save();

            $message = $this->syncFiles($event->calendar);

            $result = 'success';
            Flash::success("Deleted instances re-instated $message");
        } catch (Exception $ex) {
            Flash::error('Event not updated: ' . $ex->getMessage());
        }

        $this->prepareVars();
        return array('result' => $result);
    }

    public function onBreakLock()
    {
        $result       = 'fail';
        $user         = BackendAuth::user();
        $instanceID   = Request::input('instanceID');
        $instance     = Instance::find($instanceID);
        $eventPart    = &$instance->eventPart;
        if ($eventPart->lock($user, TRUE, FALSE, TRUE)) // save, throw, superuser_override
            $result = 'success';

        return array('result' => $result);
    }

    public function onChangeDate()
    {
        $post        = post();
        $result      = 'error';
        $newInstance = NULL;
        $type        = 'instance';

        if (isset($post['dataRequestDropID'])) {
            $newDate    = $post['dataRequestDropID'];
            $dNewDate   = new Carbon($newDate);
            $instance   = Instance::find($post['dataRequestID']);
            $eventPart  = &$instance->eventPart;
            $event      = &$eventPart->event;

            // Take in to account events spanning several days
            $start      = &$instance->instance_start;
            $length     = $instance->instance_start->diff($instance->instance_end);
            $year       = (int) $dNewDate->format('Y');
            $month      = (int) $dNewDate->format('m');
            $day        = (int) $dNewDate->format('d');
            $start->setDate($year, $month, $day);       // Maintain time
            $end        = (clone $start)->add($length); // Maintain event length

            try {
                if ($eventPart->repeat) {
                    // Repeating events
                    // Remove the instance
                    $instances_deleted = $eventPart->instances_deleted;
                    array_push($instances_deleted, $instance->instance_num);
                    $eventPart->instances_deleted = $instances_deleted; // Direct attribute modification
                    // These may throw AuthorizationException, ObjectIsLocked
                    $eventPart->save();

                    // New event part for the breakaway instance
                    $eventPart2 = $eventPart->replicate();
                    // Replicate the relations as well
                    $eventPart2->users    = $eventPart->users;
                    $eventPart2->groups   = $eventPart->groups;
                    $eventPart2->instances_deleted = NULL;
                    $eventPart2->repeat   = NULL;
                    $eventPart2->until    = NULL;
                    $eventPart2->start    = $start;
                    $eventPart2->end      = $end;
                    // These may throw AuthorizationException, ObjectIsLocked
                    $eventPart2->save();
                    $newInstance = $eventPart2->instances[0];
                } else {
                    // Non-Repeating events
                    $type = '';
                    $eventPart->start    = $start;
                    $eventPart->end      = $end;
                    // These may throw AuthorizationException, ObjectIsLocked
                    $eventPart->save();
                }

                $message = $this->syncFiles($event->calendar);

                $result = 'success';
                Flash::success("Event $type moved to $newDate $message");
            } catch (Exception $ex) {
                Flash::error("Event $type not moved: " . $ex->getMessage());
            }
        } else {
            Flash::error("Event $type cannot be dropped there");
        }

        $this->prepareVars();
        return array(
            'result' => $result,
            'newInstanceID' => ($newInstance ? $newInstance->id : NULL),
        );
    }

    public static function defaultEventSettings(string|\DateTime $date = NULL, User $user = NULL): array
    {
        if (is_null($date)) $date = new \DateTime(); // Today
        else if (! $date instanceof \DateTime) $date = new \DateTime($date);
        if (is_null($user )) $user = BackendAuth::user();

        // Default settings
        $defaultSettings = array(
            'calendar'         => 'ceea8856-e4c8-11ef-8719-5f58c97885a2', // Default hardcoded system calendar
            // TODO: Should this not be the user object or _id?
            // TODO: These should be AA\User users
            'owner_user'       => $user->id, 
            'owner_user_group' => ($user->groups ? $user->groups->first->get()->id : NULL)
        );

        $default_event_time_from = Settings::get('default_event_time_from');
        $default_event_time_to   = Settings::get('default_event_time_to');
        $timeFrom = ($default_event_time_from ? (new \DateTime($default_event_time_from))->format('H:i') : '9:00');
        $timeTo   = ($default_event_time_to   ? (new \DateTime($default_event_time_to))->format('H:i')   : '10:00');
        $defaultSettings['start'] = $date->format("Y-m-d $timeFrom");
        $defaultSettings['end']   = $date->format("Y-m-d $timeTo");

        return $defaultSettings;
    }

    public static function applyControllerFilterSettings(array $defaultSettings, array $filterCallbacks): array
    {
        $user = BackendAuth::user();

        // Override with current filter settings
        foreach ($filterCallbacks as $callback) {
            $filter = &$callback[0];
            if ($filter instanceof Filter) {
                foreach ($filter->getScopes() as &$scope) {
                    // Note that the values will be arrays, even if there is only one selection
                    if ($scope->value) {
                        switch ($scope->type) {
                            case 'daterange':
                                $defaultSettings[$scope->scopeName . '_start'] = $scope->value[0]->format('Y-m-d H:i');
                                $defaultSettings[$scope->scopeName . '_end']   = $scope->value[1]->format('Y-m-d H:i');
                                break;
                            case 'checkbox':
                                $defaultSettings[$scope->scopeName] = $scope->value;
                                break;
                            default:
                                if (is_array($scope->value) && count($scope->value)) {
                                    // We want the keys, not the texts
                                    $value = array_keys($scope->value)[0];
                                    $defaultSettings[$scope->scopeName] = $value;
                                }
                        }
                    }

                    switch ($scope->scopeName) {
                        case 'myattendance':
                            if ($scope->value) {
                                $attendees = &$defaultSettings['users'];
                                if (is_array($attendees)) {
                                    if (!in_array($attendees, $user->id)) array_push($attendees, $user->id);
                                } else $attendees = array($user->id);
                            }
                            break;
                    }
                }
            }
        }

        return $defaultSettings;
    }

    public static function createDefaultEventPart(string|\DateTime $date = NULL, User $user = NULL, array $filterCallbacks = NULL, array $attributes = []): EventPart
    {
        $defaultSettings = self::defaultEventSettings($date, $user);
        $defaultSettings = array_merge($defaultSettings, $attributes);
        $defaultSettings = self::applyControllerFilterSettings($defaultSettings, $filterCallbacks);

        // Create blank deferred objects
        $event     = new Event();
        $eventPart = new EventPart();
        $event->fill($defaultSettings);
        $eventPart->fill($defaultSettings);
        $eventPart->event = $event;

        return $eventPart;
    }

    public function prepareFormWidgetConfig(string|\DateTime $date = NULL, User $user = NULL, array $filterCallbacks = NULL): array|\stdClass
    {
        $defaultEventPart = self::createDefaultEventPart($date, $user, $filterCallbacks);

        // Make Form widget, from EventPart form
        // We use the EventPart fields which references the X-1 Event fields
        // so that 1 initial EventPart is created
        // TODO: The event fields now also show all the initial eventpart fields. Use that!
        $widgetConfig = $this->makeConfig('~/plugins/acorn/calendar/models/eventpart/fields.yaml');
        $widgetConfig->model = $defaultEventPart;
        $widgetConfig->context = 'create';

        return $widgetConfig;
    }

    public function onOpenDay() // onCreateEvent
    {
        $date = Request::input('path');
        $type = Request::input('type', 'event');

        $widgetConfig = $this->prepareFormWidgetConfig(
            $date,
            NULL, // BackendAuth::user()
            $this->filterCallbacks // For list view displays, copy the filter settings
        );    
        $widget = $this->makeWidget('Backend\Widgets\Form', $widgetConfig);
        
        $this->prepareVars();
        $this->vars['templatePath'] = $date;
        $this->vars['lastModified'] = date('U');
        $this->vars['canCommit']    = TRUE;
        $this->vars['canReset']     = TRUE;
        
        $eventPart = &$widgetConfig->model;
        $name      = "New event on " . $eventPart->start->format('Y-m-d');
        $hints     = array();
        $isPast    = $eventPart->isPast();
        $canPast   = $eventPart->canPast();
        if ($isPast) $hints[] = $this->makePartial('hint_past_event', array('canPast' => $canPast));

        return $this->makePartial('popup_create', [
            'name'   => $name,
            'hints'  => $hints,
            'form'   => $widget,
            'canWrite'      => $canPast,
            'templateType'  => $type,
            'templateTheme' => 'default',
        ]);
    }

    public function onOpenEvent()
    {
        $type         = Request::input('type', 'event');
        $instanceID   = Request::input('path');
        $user         = BackendAuth::user();
        $instance     = Instance::find($instanceID);
        if (is_null($instance)) throw new ApplicationException("Instance [$instanceID] Not Found");

        $eventPart    = $instance->eventPart;
        $event        = $eventPart->event;

        $widgetConfig = $this->makeConfig('~/plugins/acorn/calendar/models/eventpart/fields.yaml');
        $widgetConfig->model = $eventPart;
        $widgetConfig->context = 'update';
        $widget       = $this->makeWidget('Backend\Widgets\Form', $widgetConfig);

        $this->vars['templatePath'] = $eventPart->id;
        $this->vars['lastModified'] = date('U');
        $this->vars['canCommit']    = TRUE;
        $this->vars['canReset']     = TRUE;

        $eventName     = ($eventPart->name   ? e($eventPart->name) : '&lt;' . trans('no name') . '&gt;');
        $partIndex     = $eventPart->partIndex();
        $partOrdinal   = self::ordinal($partIndex + 1);
        $partName      = (count($event->event_parts) > 1 ? "<span class='part-name'>$partOrdinal part</span>" : '');

        $ordinal       = self::ordinal($instance->instance_num + 1) . ($instance->isLast() ? ' and last' : '');
        $repetition    = e(trans('repetition'));
        $instanceStart = $instance->instance_start->format('M-d');
        $instanceName  = ($eventPart->repeat && $instance->instance_num ? "<span class='instance-name'>$ordinal $repetition @ $instanceStart</span>" : '');

        // Cut-off near last word
        $eventNameFormat = $eventName;
        if (strlen($eventName) > 50) {
            $eventNameFormat = substr($eventName, 0, 50);
            $eventNameFormat = preg_replace('/ +[^ ]{0,8}$/', '', $eventNameFormat);
            $eventNameFormat = "$eventNameFormat ...";
        }

        $name  = "Edit event <span class='event-name'>$eventNameFormat</span> $partName $instanceName";
        $close = e(trans('backend::lang.relation.close'));

        $hints   = array();
        $isPast  = ($instance->instance_end < new \DateTime());
        $canPast = $instance->canPast();
        if ($isPast)             $hints[] = $this->makePartial('hint_past_event', array('canPast' => $canPast));
        if (!$event->canRead())  $hints[] = $this->makePartial('hint_cannot_read');
        if (!$event->canWrite()) $hints[] = $this->makePartial('hint_cannot_write');
        $hints[] = $this->makePartial('hint_dirty_read');

        // Event lockingfooter
        // We update the others, via WebSockets, because now this event is locked
        // This may throw, so we want a proper response, not an debugging error dialog
        if (!$eventPart->lock($user, TRUE, FALSE)) { // save, throw
            if ($user->is_superuser) $hints[] = $this->makePartial('hint_break_lock');
            else $hints[] = $this->makePartial('hint_locked');
        }

        return $this->makePartial('popup_update', [
            'name'     => $name,
            'instance' => $instance,
            'hints'    => $hints,
            'form'     => $widget,
            'templateType'  => $type,
            'templateTheme' => 'default',
        ]);
    }
}
