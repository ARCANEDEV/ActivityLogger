<?php namespace Arcanedev\ActivityLogger\Tests\Traits;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\Article;
use Arcanedev\ActivityLogger\Tests\TestCase;
use Arcanedev\ActivityLogger\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class     LogsActivityTest
 *
 * @package  Arcanedev\ActivityLogger\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class LogsActivityTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var \Arcanedev\ActivityLogger\Tests\Stubs\Models\Article|\Arcanedev\ActivityLogger\Traits\LogsActivity */
    protected $article;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp()
    {
        parent::setUp();

        $this->article = new class() extends Article {
            use LogsActivity,
                SoftDeletes;
        };

        static::assertCount(0, Activity::all());
    }

    /** @test */
    public function it_will_log_the_creation_of_the_model()
    {
        $article = $this->createArticle();
        static::assertCount(1, Activity::all());

        static::assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        static::assertEquals($article->id, $this->getLastActivity()->subject->id);
        static::assertEquals('created', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_skip_logging_model_events_if_asked_to()
    {
        $article = new $this->article();
        $article->disableLogging();
        $article->name = 'my name';
        $article->save();

        static::assertSame(0, Activity::query()->count());
        static::assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_can_switch_on_activity_logging_after_disabling_it()
    {
        $article = new $this->article();

        $article->disableLogging();
        $article->name = 'my name';
        $article->save();

        $article->enableLogging();
        $article->name = 'my new name';
        $article->save();

        static::assertCount(1, Activity::all());
        static::assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        static::assertEquals($article->id, $this->getLastActivity()->subject->id);
        static::assertEquals('updated', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_skip_logging_if_asked_to_for_update_method()
    {
        $article = new $this->article();
        $article->disableLogging()->update(['name' => 'How to log events']);

        static::assertCount(0, Activity::all());
        static::assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_will_log_an_update_of_the_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        static::assertCount(2, Activity::all());

        static::assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        static::assertEquals($article->id, $this->getLastActivity()->subject->id);
        static::assertEquals('updated', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_without_soft_deletes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;
        };

        $article = new $articleClass();

        $article->save();

        static::assertEquals('created', $this->getLastActivity()->description);

        $article->delete();

        static::assertEquals('deleted', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        static::assertCount(2, Activity::all());

        static::assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        static::assertEquals($article->id, $this->getLastActivity()->subject_id);
        static::assertEquals('deleted', $this->getLastActivity()->description);

        $article->forceDelete();

        static::assertCount(3, Activity::all());

        static::assertEquals('deleted', $this->getLastActivity()->description);
        static::assertNull($article->fresh());
    }

    /** @test */
    public function it_will_log_the_restoring_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        $article->restore();

        static::assertCount(3, Activity::all());

        static::assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        static::assertEquals($article->id, $this->getLastActivity()->subject_id);
        static::assertEquals('restored', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_fetch_all_activity_for_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        static::assertCount(2, $article->activities);
    }

    /** @test */
    public function it_can_fetch_soft_deleted_models()
    {
        $this->app['config']->set('activity-logger.subjects.soft-deleted', true);

        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $article->delete();

        static::assertCount(3, $article->activities);

        static::assertSame(get_class($this->article), $this->getLastActivity()->subject_type);
        static::assertEquals($article->id, $this->getLastActivity()->subject_id);
        static::assertSame('deleted', $this->getLastActivity()->description);
        static::assertSame('changed name', $this->getLastActivity()->subject->name);
    }

    /** @test */
    public function it_can_log_activity_to_log_returned_from_model_method_override()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getLogNameToUse()
            {
                return 'custom_log';
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        static::assertEquals($article->id, Activity::inLog('custom_log')->first()->subject->id);
        static::assertCount(1, Activity::inLog('custom_log')->get());
    }

    /** @test */
    public function it_can_log_activity_to_log_named_in_the_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected static $logName = 'custom_log';
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        static::assertSame('custom_log', Activity::latest()->first()->log_name);
    }

    /** @test */
    public function it_will_not_log_an_update_of_the_model_if_only_ignored_attributes_are_changed()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            /**
             * Get the attributes that should be ignored.
             *
             * @return array
             */
            public function ignoredAttributesFromLogging()
            {
                return ['text'];
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $article->text = 'ignore me';
        $article->save();

        static::assertCount(1, Activity::all());

        static::assertInstanceOf(get_class($articleClass), $this->getLastActivity()->subject);
        static::assertEquals($article->id, $this->getLastActivity()->subject->id);
        static::assertEquals('created', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_not_fail_if_asked_to_replace_from_empty_attribute()
    {
        $model = new class() extends Article {
            use LogsActivity,
                SoftDeletes;

            public function getDescriptionForEvent($eventName)
            {
                return ":causer.name $eventName";
            }
        };

        $entity = new $model();
        $entity->save();
        $entity->name = 'my name';
        $entity->save();

        $activities = $entity->activities;

        static::assertCount(2, $activities);
        static::assertEquals($entity->id, $activities[0]->subject->id);
        static::assertEquals($entity->id, $activities[1]->subject->id);
        static::assertEquals(':causer.name created', $activities[0]->description);
        static::assertEquals(':causer.name updated', $activities[1]->description);
    }

    /**
     * Create an article.
     *
     * @return \Arcanedev\ActivityLogger\Tests\Stubs\Models\Article
     */
    protected function createArticle()
    {
        return tap(new $this->article(), function ($article) {
            $article->name = 'my name';
            $article->save();
        });
    }
}
