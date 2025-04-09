<div class="layout-cell calendar-day <?= implode(' ', $classes) ?>" style="<?= implode(';', $styles) ?>">
    <div class="layout-row calendar-day-header">
        <?php if ($range == 'in'): ?>
            <a href="#" class="event-add"
                data-handler="onOpenDay"
                data-request-data="path:'<?= $date->format('Y-m-d') ?>', objectType:'Event'"
                data-control="popup"
                title="<?= e(trans('acorn.calendar::lang.models.calendar.day_add_event')) ?>"
            ><?= e(trans('acorn.calendar::lang.models.calendar.day_add_event')) ?></a>
        <?php endif ?>
        <span class="title"><?= $title ?></span>
        <?php
            // TODO: Make an AA Module Carbon derived class to encapsulate this date translation functionality
            // https://stackoverflow.com/questions/4975854/translating-php-date-for-multilingual-site
            // $fmt = datefmt_create(
            //     'pt_BR', // The output language.
            //     pattern: "cccc, d 'de' LLLL 'de' YYYY" // The output formatting.
            // );

            // Escape our special patterns prior to standard format()
            // M* => \M*
            $format = preg_replace('/([a-zA-Z])\*/', '\\\\$1*', $format);
            // Process standard DateTime formats
            $standardOutput = $date->format($format);
            // Process our translateable formats: M*
            // Note that format() will have removed the backslashes \
            $monthNum             = (int) $date->format('n'); // 1-12
            $translatedMonthNames = trans('acorn.calendar::lang.models.calendar.months');
            $translatedMonthName  = $translatedMonthNames[$monthNum-1];
            $standardOutput       = str_replace('M*', $translatedMonthName, $standardOutput);
            
            print($standardOutput);
        ?>
    </div>
    <ul class="calendar-event-list drop-target" data-request-drop-id="<?= $date->format('Y-m-d') ?>">
        <?php $e = 0; while (isset($events[$e])) print($this->makePartial('instance', [
            'instance'  => $events[$e],
            'eventPart' => $events[$e++]->eventPart,
            'tag'       => 'li'
        ])); ?>
    </ul>
</div>
