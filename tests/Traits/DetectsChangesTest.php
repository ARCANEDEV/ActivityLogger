<?php namespace Arcanedev\ActivityLogger\Tests\Traits;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\Article;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Arcanedev\ActivityLogger\Tests\TestCase;
use Arcanedev\ActivityLogger\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class     DetectsChangesTest
 *
 * @package  Arcanedev\ActivityLogger\Tests\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class DetectsChangesTest extends TestCase
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
            public static $logAttributes = ['name', 'text'];

            use LogsActivity;
        };
        static::assertSame(0, Activity::query()->count());
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_store_the_values_when_creating_a_model()
    {
        $this->createArticle();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_relation_values_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
        };

        $user = User::query()->create([
            'name' => 'user name',
        ]);

        $article = $articleClass::query()->create([
            'name'    => 'original name',
            'text'    => 'original text',
            'user_id' => $user->id,
        ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        static::assertEquals(
            [
                'attributes' => [
                    'name'      => 'updated name',
                    'text'      => 'updated text',
                    'user.name' => 'user name',
                ],
                'old' => [
                    'name'      => 'original name',
                    'text'      => 'original text',
                    'user.name' => 'user name',
                ],
            ],
            $this->getLastActivity()->changes()->toArray()
        );
    }

    /** @test */
    public function it_can_store_empty_relation_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
        };

        User::query()->create([
            'name' => 'user name',
        ]);

        $article = $articleClass::query()->create([
            'name' => 'original name',
            'text' => 'original text',
        ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
                'text' => 'updated text',
                'user.name' => null,
            ],
            'old' => [
                'name' => 'original name',
                'text' => 'original text',
                'user.name' => null,
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'updated name';
        $article->text = 'updated text';

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
                'text' => 'updated text',
            ],
            'old' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_dirty_changes_only()
    {
        $article = $this->createDirtyArticle();

        $article->name = 'updated name';

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
            ],
            'old' => [
                'name' => 'my name',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_dirty_changes_for_swapping_values()
    {
        $article = $this->createDirtyArticle();

        $originalName = $article->name;
        $originalText = $article->text;

        $article->text = $originalName;
        $article->name = $originalText;

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => $originalText,
                'text' => $originalName,
            ],
            'old' => [
                'name' => $originalName,
                'text' => $originalText,
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_related_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
        };

        $user = User::query()->create([
            'name' => 'a name',
        ]);

        $anotherUser = User::query()->create([
            'name' => 'another name',
        ]);

        $article = $articleClass::query()->create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'name',
                'text' => 'text',
                'user.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'user.name' => 'a name',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_dirty_changes_when_updating_a_related_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            public static $logOnlyDirty = true;

            use LogsActivity;
        };

        $user = User::query()->create([
            'name' => 'a name',
        ]);

        $anotherUser = User::query()->create([
            'name' => 'another name',
        ]);

        $article = $articleClass::query()->create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'user.name' => 'another name',
            ],
            'old' => [
                'user.name' => 'a name',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_will_store_no_changes_when_not_logging_attributes()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = [];

            use LogsActivity;
        };

        $article = new $articleClass();

        $article->name = 'updated name';

        $article->save();

        static::assertEquals(collect(), $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_will_store_the_values_when_deleting_the_model()
    {
        $article = $this->createArticle();

        $article->delete();

        $expectedChanges = collect([
            'attributes' => [
                'name' => 'my name',
            ],
        ]);

        static::assertEquals('deleted', $this->getLastActivity()->description);
        static::assertEquals($expectedChanges, $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_will_store_the_values_when_deleting_the_model_with_softdeletes()
    {
        $article = new class() extends Article {
            public static $logAttributes = ['name', 'text'];

            use LogsActivity,
                SoftDeletes;
        };

        $article->name = 'my name';
        $article->save();

        $article->delete();

        $expectedChanges = collect([
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ]);

        static::assertEquals('deleted', $this->getLastActivity()->description);
        static::assertEquals($expectedChanges, $this->getLastActivity()->changes());

        $article->forceDelete();

        $expectedChanges = collect([
            'attributes' => [
                'name' => 'my name',
            ],
        ]);

        static::assertCount(3, $article->activities);
        static::assertEquals('deleted', $this->getLastActivity()->description);
        static::assertEquals($expectedChanges, $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_can_store_the_changes_of_array_casted_properties()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['json'];
            public static $logOnlyDirty = true;
            protected $casts = ['json' => 'collection'];

            use LogsActivity;
        };

        $article = $articleClass::query()->create([
            'json' => ['value' => 'original'],
        ]);

        $article->json = collect(['value' => 'updated']);
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'value' => 'updated',
                ],
            ],
            'old' => [
                'json' => [
                    'value' => 'original',
                ],
            ],
        ];
        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_nothing_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            protected $fillable = ['name', 'text'];
            protected static $logFillable = false;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_text_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['text'];
            protected static $logFillable = false;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => 'my text',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_fillable_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            protected $fillable = ['name', 'text'];
            protected static $logFillable = true;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_both_fillable_and_log_attributes()
    {
        $articleClass = new class() extends Article {
            protected $fillable = ['name'];
            protected static $logAttributes = ['text'];
            protected static $logFillable = true;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_for_loggable_attributes()
    {
        $article = new class() extends Article {
            public static $logAttributes = ['*'];

            use LogsActivity;
        };
        $article->name = 'my name';

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name'       => 'my name',
                'text'       => null,
                'deleted_at' => null,
                'id'         => $article->id,
                'user_id'    => null,
                'json'       => null,
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_with_relation()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*', 'user.name'];

            use LogsActivity;
        };

        $user = User::query()->create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));

        $article = $articleClass::query()->create([
            'name' => 'article name',
            'text' => 'article text',
            'user_id' => $user->id,
        ]);

        $expectedChanges = [
            'attributes' => [
                'id' => $article->id,
                'name' => 'article name',
                'text' => 'article text',
                'deleted_at' => null,
                'user_id' => $user->id,
                'json' => null,
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'user.name' => 'user name',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_when_updating_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logOnlyDirty = true;

            use LogsActivity;
        };

        $user = User::query()->create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article = $articleClass::query()->create([
            'name' => 'article name',
            'text' => 'article text',
            'user_id' => $user->id,
        ]);

        $article->name = 'changed name';
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'changed name',
                'updated_at' => '2018-01-01 12:00:00',
            ],
            'old' => [
                'name' => 'article name',
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_ignored_attributes_while_updating()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logAttributesToIgnore = ['name', 'updated_at'];

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => null,
                'deleted_at' => null,
                'id' => $article->id,
                'user_id' => null,
                'json' => null,
                'created_at' => '2017-01-01 12:00:00',
            ],
        ];

        static::assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    protected function createArticle()
    {
        return tap(new $this->article, function ($article) {
            $article->name = 'my name';
            $article->save();
        });
    }

    protected function createDirtyArticle()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text'];

            public static $logOnlyDirty = true;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}
