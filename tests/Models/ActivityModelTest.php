<?php namespace Arcanedev\ActivityLogger\Tests\Models;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\Article;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Arcanedev\ActivityLogger\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class     ActivityModelTest
 *
 * @package  Arcanedev\ActivityLogger\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class ActivityModelTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp()
    {
        parent::setUp();

        collect(range(1, 5))->each(function (int $index) {
            $logName = "log{$index}";
            activity($logName)->track('hello everybody');
        });
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_provides_a_scope_to_get_activities_from_a_specific_log()
    {
        $activityInLog3 = Activity::inLog('log3')->get();

        $this->assertCount(1, $activityInLog3);

        $this->assertEquals('log3', $activityInLog3->first()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs()
    {
        $activity = Activity::inLog('log2', 'log4')->get();

        $this->assertCount(2, $activity);

        $this->assertEquals('log2', $activity->first()->log_name);
        $this->assertEquals('log4', $activity->last()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs_using_an_array()
    {
        $activity = Activity::inLog(['log1', 'log2'])->get();

        $this->assertCount(2, $activity);

        $this->assertEquals('log1', $activity->first()->log_name);
        $this->assertEquals('log2', $activity->last()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_causer()
    {
        $subject = Article::query()->first();
        $causer = User::query()->first();

        activity()->on($subject)->by($causer)->track('Foo');
        activity()->on($subject)->by(User::query()->create([
            'name' => 'Another User',
        ]))->track('Bar');

        $activities = Activity::causedBy($causer)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
        $this->assertEquals(get_class($causer), $activities->first()->causer_type);
        $this->assertEquals('Foo', $activities->first()->description);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_subject()
    {
        $subject = Article::query()->first();
        $causer = User::query()->first();

        activity()->on($subject)->by($causer)->track('Foo');
        activity()->on(Article::query()->create([
            'name' => 'Another article',
        ]))->by($causer)->track('Bar');

        $activities = Activity::forSubject($subject)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
        $this->assertEquals(get_class($subject), $activities->first()->subject_type);
        $this->assertEquals('Foo', $activities->first()->description);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_morphmapped_causer()
    {
        Relation::morphMap([
            'articles' => Article::class,
            'users'    => User::class,
        ]);

        $subject = Article::query()->first();
        $causer  = User::query()->first();

        activity()->on($subject)->by($causer)->track('Foo');
        activity()->on($subject)->by(User::query()->create([
            'name' => 'Another User',
        ]))->track('Bar');

        $activities = Activity::causedBy($causer)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
        $this->assertEquals('users', $activities->first()->causer_type);
        $this->assertEquals('Foo', $activities->first()->description);

        Relation::morphMap([], false);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_morphmapped_subject()
    {
        Relation::morphMap([
            'articles' => Article::class,
            'users'    => User::class,
        ]);

        $subject = Article::query()->first();
        $causer = User::query()->first();

        activity()->on($subject)->by($causer)->track('Foo');
        activity()->on(Article::query()->create([
            'name' => 'Another article',
        ]))->by($causer)->track('Bar');

        $activities = Activity::forSubject($subject)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
        $this->assertEquals('articles', $activities->first()->subject_type);
        $this->assertEquals('Foo', $activities->first()->description);

        Relation::morphMap([], false);
    }
}
