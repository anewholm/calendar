<?php namespace Acorn\Calendar\Behaviors;

use Lang;
use Event;
use Flash;
use ApplicationException;
use Backend\Classes\ControllerBehavior;

/** 
 * Adds features for working with backend calendars.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         \Acorn\Calendar\Behaviors\CalendarController::class,
 *     ];
 *
 *     public $monthConfig = 'config_calendar.yaml';
 *
 * The `$monthConfig` property makes reference to the calendar configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package acorn/calendar
 * @author sz
 */
class CalendarController extends ControllerBehavior
{
    /**
     * @var array Calendar definitions, keys for alias and value for configuration.
     */
    protected $calendarDefinitions;

    /**
     * @var string The primary calendar alias to use. Default: calendar
     */
    protected $primaryDefinition;

    /**
     * @var \Backend\Classes\WidgetBase[] Reference to the calendar widget object.
     */
    protected $calendarWidgets = [];

    /**
     * @var \Backend\Classes\WidgetBase[] Reference to the toolbar widget objects.
     */
    protected $toolbarWidgets = [];

    /**
     * @var \Backend\Classes\WidgetBase[] Reference to the filter widget objects.
     */
    protected $filterWidgets = [];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     * - instance: Calendar column definitions
     */
    protected $requiredConfig = ['modelClass', 'instance'];

    /**
     * @var array Visible actions in context of the controller
     */
    protected $actions = ['index'];

    /**
     * @var mixed Configuration for this behaviour
     */
    public $monthConfig = 'config_month.yaml';

    /**
     * Behavior constructor
     * @param \Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        /*
         * Extract calendar definitions
         */
        $config = $controller->monthConfig ?: $this->monthConfig;
        if (is_array($config)) {
            $this->calendarDefinitions = $config;
            $this->primaryDefinition = key($this->calendarDefinitions);
        }
        else {
            $this->calendarDefinitions = ['instance' => $config];
            $this->primaryDefinition = 'instance';
        }

        /*
         * Build configuration
         */
        $this->setConfig($this->calendarDefinitions[$this->primaryDefinition], $this->requiredConfig);
    }

    /**
     * Creates all the calendar widgets based on the definitions.
     * @return array
     */
    public function makeCalendars()
    {
        foreach ($this->calendarDefinitions as $definition => $config) {
            $this->calendarWidgets[$definition] = $this->makeCalendar($definition);
        }

        return $this->calendarWidgets;
    }

    /**
     * Prepare the widgets used by this action
     * @return \Backend\Widgets\Calendars
     */
    public function makeCalendar($definition = null)
    {
        if (!$definition || !isset($this->calendarDefinitions[$definition])) {
            $definition = $this->primaryDefinition;
        }

        $monthConfig = $this->controller->calendarGetConfig($definition);

        /*
         * Create the model
         */
        $class = $monthConfig->modelClass;
        $model = new $class;
        $model = $this->controller->calendarExtendModel($model, $definition);

        /*
         * Prepare the calendar widget
         */
        $columnConfig = $this->makeConfig($monthConfig->instance);
        $columnConfig->model = $model;
        $columnConfig->alias = $definition;

        /*
         * Prepare the columns configuration
         */
        $configFieldsToTransfer = [
            'recordUrl',
            'recordOnClick',
            'recordsPerPage',
            'perPageOptions',
            'showPageNumbers',
            'noRecordsMessage',
            'defaultSort',
            'showSorting',
            'showSetup',
            'showCheckboxes',
            'showTree',
            'treeExpanded',
            'customViewPath',
            'columns',
        ];

        foreach ($configFieldsToTransfer as $field) {
            if (isset($monthConfig->{$field})) {
                $columnConfig->{$field} = $monthConfig->{$field};
            }
        }

        /*
         * Calendar Widget with extensibility
         */
        $widget = $this->makeWidget(\Acorn\Calendar\Widgets\Calendars::class, $columnConfig);

        $widget->bindEvent('calendar.extendColumns', function () use ($widget) {
            $this->controller->calendarExtendColumns($widget);
        });

        $widget->bindEvent('calendar.extendQueryBefore', function ($query) use ($definition) {
            $this->controller->calendarExtendQueryBefore($query, $definition);
        });

        $widget->bindEvent('calendar.extendQuery', function ($query) use ($definition) {
            $this->controller->calendarExtendQuery($query, $definition);
        });

        $widget->bindEvent('calendar.extendRecords', function ($records) use ($definition) {
            return $this->controller->calendarExtendRecords($records, $definition);
        });

        $widget->bindEvent('calendar.injectRowClass', function ($record) use ($definition) {
            return $this->controller->calendarInjectRowClass($record, $definition);
        });

        $widget->bindEvent('calendar.overrideColumnValue', function ($record, $column, $value) use ($definition) {
            return $this->controller->calendarOverrideColumnValue($record, $column->columnName, $definition);
        });

        $widget->bindEvent('calendar.overrideHeaderValue', function ($column, $value) use ($definition) {
            return $this->controller->calendarOverrideHeaderValue($column->columnName, $definition);
        });

        $widget->bindToController();

        /*
         * Prepare the toolbar widget (optional)
         */
        if (isset($monthConfig->toolbar)) {
            $toolbarConfig = $this->makeConfig($monthConfig->toolbar);
            $toolbarConfig->alias = $widget->alias . 'Toolbar';
            $toolbarWidget = $this->makeWidget(\Backend\Widgets\Toolbar::class, $toolbarConfig);
            $toolbarWidget->bindToController();
            $toolbarWidget->cssClasses[] = 'calendar-header';

            /*
             * Link the Search Widget to the Calendar Widget
             */
            if ($searchWidget = $toolbarWidget->getSearchWidget()) {
                $searchWidget->bindEvent('search.submit', function () use ($widget, $searchWidget) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm(), true);
                    return $widget->onRefresh();
                });

                $widget->setSearchOptions([
                    'mode' => $searchWidget->mode,
                    'scope' => $searchWidget->scope,
                ]);

                // Find predefined search term
                $widget->setSearchTerm($searchWidget->getActiveTerm());
            }

            $this->toolbarWidgets[$definition] = $toolbarWidget;
        }

        /*
         * Prepare the filter widget (optional)
         */
        if (isset($monthConfig->filter)) {
            $filterConfig = $this->makeConfig($monthConfig->filter);

            if (!empty($filterConfig->scopes)) {
                $widget->cssClasses[] = 'calendar-flush';

                $filterConfig->alias = $widget->alias . 'Filter';
                $filterWidget = $this->makeWidget(\Backend\Widgets\Filter::class, $filterConfig);
                $filterWidget->bindToController();

                /*
                * Filter the calendar when the scopes are changed
                */
                $filterWidget->bindEvent('filter.update', function () use ($widget, $filterWidget) {
                    return $widget->onFilter();
                });

                /*
                * Filter Widget with extensibility
                */
                $filterWidget->bindEvent('filter.extendScopes', function () use ($filterWidget) {
                    $this->controller->calendarFilterExtendScopes($filterWidget);
                });

                /*
                * Extend the query of the calendar of options
                */
                $filterWidget->bindEvent('filter.extendQuery', function ($query, $scope) {
                    $this->controller->calendarFilterExtendQuery($query, $scope);
                });

                // Apply predefined filter values
                $widget->addFilter([$filterWidget, 'applyAllScopesToQuery']);

                $this->filterWidgets[$definition] = $filterWidget;
            }
        }

        return $widget;
    }

    /**
     * Index Controller action.
     * @return void
     */
    public function index()
    {
        $this->controller->pageTitle = $this->controller->pageTitle ?: Lang::get($this->getConfig(
            'title',
            'backend::lang.calendar.default_title'
        ));
        $this->controller->bodyClass = 'slim-container';
        $this->makeCalendars();
    }

    /**
     * Bulk delete records.
     * @return void
     * @throws \Winter\Storm\Exception\ApplicationException when the parent definition is missing.
     */
    public function index_onDelete()
    {
        if (method_exists($this->controller, 'onDelete')) {
            return call_user_func_array([$this->controller, 'onDelete'], func_get_args());
        }

        /*
         * Establish the calendar definition
         */
        $definition = post('definition', $this->primaryDefinition);

        if (!isset($this->calendarDefinitions[$definition])) {
            throw new ApplicationException(Lang::get('backend::lang.calendar.missing_parent_definition', compact('definition')));
        }

        $monthConfig = $this->controller->calendarGetConfig($definition);

        /*
         * Validate checked identifiers
         */
        $checkedIds = post('checked');

        if (!$checkedIds || !is_array($checkedIds) || !count($checkedIds)) {
            Flash::error(Lang::get(
                (!empty($monthConfig->noRecordsDeletedMessage))
                    ? $monthConfig->noRecordsDeletedMessage
                    : 'backend::lang.calendar.delete_selected_empty'
            ));
            return $this->controller->calendarRefresh();
        }

        /*
         * Create the model
         */
        $class = $monthConfig->modelClass;
        $model = new $class;
        $model = $this->controller->calendarExtendModel($model, $definition);

        /*
         * Create the query
         */
        $query = $model->newQuery();
        $this->controller->calendarExtendQueryBefore($query, $definition);

        $query->whereIn($model->getKeyName(), $checkedIds);
        $this->controller->calendarExtendQuery($query, $definition);

        /*
         * Delete records
         */
        $records = $query->get();

        if ($records->count()) {
            foreach ($records as $record) {
                $record->delete();
            }

            Flash::success(Lang::get(
                (!empty($monthConfig->deleteMessage))
                    ? $monthConfig->deleteMessage
                    : 'backend::lang.calendar.delete_selected_success'
            ));
        }
        else {
            Flash::error(Lang::get(
                (!empty($monthConfig->noRecordsDeletedMessage))
                    ? $monthConfig->noRecordsDeletedMessage
                    : 'backend::lang.calendar.delete_selected_empty'
            ));
        }

        return $this->controller->calendarRefresh($definition);
    }

    /**
     * Renders the widget collection.
     * @param  string $definition Optional calendar definition.
     * @return string Rendered HTML for the calendar.
     * @throws \Winter\Storm\Exception\ApplicationException when there are no calendar widgets set.
     */
    public function calendarRender($definition = null)
    {
        if (!count($this->calendarWidgets)) {
            throw new ApplicationException(Lang::get('backend::lang.calendar.behavior_not_ready'));
        }

        if (!$definition || !isset($this->calendarDefinitions[$definition])) {
            $definition = $this->primaryDefinition;
        }

        $vars = [
            'toolbar'  => null,
            'filter'   => null,
            'calendar' => null,
        ];

        if (isset($this->toolbarWidgets[$definition])) {
            $vars['toolbar'] = $this->toolbarWidgets[$definition];
        }

        if (isset($this->filterWidgets[$definition])) {
            $vars['filter'] = $this->filterWidgets[$definition];
        }

        $vars['calendar'] = $this->calendarWidgets[$definition];

        if (isset($_GET['debug'])) {
            $classname = get_class($this);
            print("<div class='debug debug-behavior'>$classname::calendarRender($definition)</div>");
        }
        return $this->calendarMakePartial('container', $vars);
    }

    /**
     * Controller accessor for making partials within this behavior.
     * @param string $partial
     * @param array $params
     * @return string Partial contents
     */
    public function calendarMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('calendar_'.$partial, $params + $this->vars, false);
        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }

    /**
     * Refreshes the calendar container only, useful for returning in custom AJAX requests.
     *
     * @return array The calendar element selector as the key, and the calendar contents are the value.
     */
    public function calendarRefresh(string $definition = null)
    {
        if (!count($this->calendarWidgets)) {
            $this->makeCalendars();
        }

        if (!$definition || !isset($this->calendarDefinitions[$definition])) {
            $definition = $this->primaryDefinition;
        }

        return $this->calendarWidgets[$definition]->onRefresh();
    }

    /**
     * Returns the widget used by this behavior.
     * @return \Backend\Classes\WidgetBase
     */
    public function calendarGetWidget(string $definition = null)
    {
        if (!$definition) {
            $definition = $this->primaryDefinition;
        }

        return array_get($this->calendarWidgets, $definition);
    }

    /**
     * Returns the configuration used by this behavior.
     * @return stdClass
     */
    public function calendarGetConfig(string $definition = null)
    {
        if (!$definition) {
            $definition = $this->primaryDefinition;
        }

        if (
            !($config = array_get($this->calendarDefinitions, $definition))
            || !is_object($config)
        ) {
            $config = $this->calendarDefinitions[$definition] = $this->makeConfig($this->calendarDefinitions[$definition], $this->requiredConfig);
        }

        return $config;
    }

    //
    // Overrides
    //

    /**
     * Called after the calendar columns are defined.
     * @param \Backend\Widgets\Calendars $host The hosting calendar widget
     * @return void
     */
    public function calendarExtendColumns($host)
    {
    }

    /**
     * Called after the filter scopes are defined.
     * @param \Backend\Widgets\Filter $host The hosting filter widget
     * @return void
     */
    public function calendarFilterExtendScopes($host)
    {
    }

    /**
     * Controller override: Extend supplied model
     * @param \Winter\Storm\Database\Model $model
     * @param string|null $definition
     * @return \Winter\Storm\Database\Model
     */
    public function calendarExtendModel($model, $definition = null)
    {
        return $model;
    }

    /**
     * Controller override: Extend the query used for populating the calendar
     * before the default query is processed.
     * @param \Winter\Storm\Database\Builder $query
     * @param string|null $definition
     */
    public function calendarExtendQueryBefore($query, $definition = null)
    {
    }

    /**
     * Controller override: Extend the query used for populating the calendar
     * after the default query is processed.
     * @param \Winter\Storm\Database\Builder $query
     * @param string|null $definition
     */
    public function calendarExtendQuery($query, $definition = null)
    {
    }

    /**
     * Controller override: Extend the records used for populating the calendar
     * after the query is processed.
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection $records
     * @param string|null $definition
     */
    public function calendarExtendRecords($records, $definition = null)
    {
    }

    /**
     * Controller override: Extend the query used for populating the filter
     * options before the default query is processed.
     * @param \Winter\Storm\Database\Builder $query
     * @param array $scope
     */
    public function calendarFilterExtendQuery($query, $scope)
    {
    }

    /**
     * Returns a CSS class name for a calendar row (<tr class="...">).
     * @param  \Winter\Storm\Database\Model $record The populated model used for the column
     * @param  string|null $definition Calendar definition (optional)
     * @return string|void CSS class name
     */
    public function calendarInjectRowClass($record, $definition = null)
    {
    }

    /**
     * Replace a table column value (<td>...</td>)
     * @param  \Winter\Storm\Database\Model $record The populated model used for the column
     * @param  string $columnName The column name to override
     * @param  string|null $definition Calendar definition (optional)
     * @return string|void HTML view
     */
    public function calendarOverrideColumnValue($record, $columnName, $definition = null)
    {
    }

    /**
     * Replace the entire table header contents (<th>...</th>) with custom HTML
     * @param  string $columnName The column name to override
     * @param  string|null $definition Calendar definition (optional)
     * @return string|void HTML view
     */
    public function calendarOverrideHeaderValue($columnName, $definition = null)
    {
    }

    /**
     * Static helper for extending calendar columns.
     * @param  callable $callback
     * @return void
     */
    public static function extendCalendarColumns($callback)
    {
        $calledClass = self::getCalledExtensionClass();
        Event::calendaren('backend.calendar.extendColumns', function (\Backend\Widgets\Calendars $widget) use ($calledClass, $callback) {
            if (!is_a($widget->getController(), $calledClass)) {
                return;
            }
            call_user_func_array($callback, [$widget, $widget->model]);
        });
    }

    /**
     * Static helper for extending filter scopes.
     * @param  callable $callback
     * @return void
     */
    public static function extendCalendarFilterScopes($callback)
    {
        $calledClass = self::getCalledExtensionClass();
        Event::calendaren('backend.filter.extendScopes', function (\Backend\Widgets\Filter $widget) use ($calledClass, $callback) {
            if (!is_a($widget->getController(), $calledClass)) {
                return;
            }
            call_user_func_array($callback, [$widget]);
        });
    }
}
