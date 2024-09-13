<?php namespace Acorn\Calendar\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Backend\Models\User;
use Acorn\Calendar\Models\EventPart;

class RunCommand extends Command
{
    protected $name = 'calendar:run';
    protected $description = 'Runs the reminder alarms.';

    public function handle()
    {
        $verbose = $this->option('verbose');
        $this->info("Reminder notifications running");

        while (TRUE) {
            foreach (EventPart::whereNotNull('alarm') as $eventPart) {
                $this->info($eventPart->name);
            }
            sleep(5);
        }
    }

    protected function getArguments()
    {
        return [];
    }

    protected function getOptions()
    {
        return [];
    }

}
