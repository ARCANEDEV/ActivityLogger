<?php namespace Arcanedev\ActivityLogger\Tests\Commands;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\TestCase;
use Illuminate\Support\Carbon;

/**
 * Class     CleanCommandTest
 *
 * @package  Arcanedev\ActivityLogger\Tests\Commands
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class CleanCommandTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 00, 00, 00));

        $this->app['config']->set('activity-logger.delete-records-older-than-days', 31);
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_clean_the_activity_log()
    {
        static::createLogs();

        static::assertSame(60, Activity::query()->count());

        $this->artisan('activity-logger:clean');

        static::assertSame(31, Activity::query()->count());

        $cutOffDate = now()->subDays(31)->format('Y-m-d H:i:s');

        static::assertSame(0, Activity::query()->where('created_at', '<', $cutOffDate)->count());
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    private static function createLogs($times = 60)
    {
        collect(range(1, $times))->each(function (int $index) {
            Activity::query()->forceCreate([
                'description' => "item {$index}",
                'created_at'  => now()->subDays($index)->startOfDay(),
            ]);
        });
    }
}
