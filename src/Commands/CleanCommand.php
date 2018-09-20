<?php namespace Arcanedev\ActivityLogger\Commands;

use Arcanedev\ActivityLogger\Contracts\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class     CleanCommand
 *
 * @package  Arcanedev\ActivityLogger\Commands
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class CleanCommand extends Command
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'activity-logger:clean {log? : (optional) The log name that will be cleaned.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up the old records from the activity log.';

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function handle()
    {
        $this->comment('Cleaning activity log...');

        $amountDeleted = $this->deleteLogs(
            $this->getCutOffDate(),
            $this->argument('log')
        );

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");
        $this->comment('All done!');
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Delete the activities logs.
     *
     * @param  string  $cutOffDate
     * @param  string  $log
     *
     * @return int
     */
    private function deleteLogs($cutOffDate, $log)
    {
        return $this->getLogger()
            ->newActivity()
            ->where('created_at', '<', $cutOffDate)
            ->when($log !== null, function (Builder $query) use ($log) {
                return $query->inLog($log);
            })
            ->delete();
    }

    /**
     * Get the cutoff date.
     *
     * @return string
     */
    private function getCutOffDate()
    {
        return now()
            ->subDays(config('activity-logger.delete-records-older-than-days'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Get the logger.
     *
     * @return \Arcanedev\ActivityLogger\Contracts\ActivityLogger
     */
    private function getLogger()
    {
        return $this->laravel->make(ActivityLogger::class);
    }
}
