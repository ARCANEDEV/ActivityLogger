<?php namespace Arcanedev\ActivityLogger\Tests;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\Article;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Illuminate\Support\Collection;

/**
 * Class     ActivityLoggerTest
 *
 * @package  Arcanedev\ActivityLogger\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class ActivityLoggerTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var  \Arcanedev\ActivityLogger\Contracts\ActivityLogger */
    private $logger;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp()
    {
        parent::setUp();

        $this->logger = $this->app->make(\Arcanedev\ActivityLogger\Contracts\ActivityLogger::class);
    }

    protected function tearDown()
    {
        unset($this->logger);

        parent::tearDown();
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_be_instantiated_via_contract()
    {
        $expectations = [
            \Psr\Log\LoggerInterface::class,
            \Arcanedev\ActivityLogger\Contracts\ActivityLogger::class,
            \Arcanedev\ActivityLogger\ActivityLogger::class,
        ];

        foreach ($expectations as $expected) {
            static::assertInstanceOf($expected, $this->logger);
        }
    }

    /** @test */
    public function it_can_be_instantiated_via_helper()
    {
        $logger       = \activity();
        $expectations = [
            \Psr\Log\LoggerInterface::class,
            \Arcanedev\ActivityLogger\Contracts\ActivityLogger::class,
            \Arcanedev\ActivityLogger\ActivityLogger::class,
        ];

        foreach ($expectations as $expected) {
            static::assertInstanceOf($expected, $logger);
        }
    }

    /** @test */
    public function it_can_log_an_activity()
    {
        $activity = $this->logger->track('My activity');

        $this->assertDatabaseHas($activity->getTable(), [
            'id'          => $activity->id,
            'log_name'    => 'default',
            'log_level'   => 'info',
            'properties'  => "[]",
            'description' => 'My activity',
        ]);
    }

    /** @test */
    public function it_will_not_log_an_activity_when_the_log_is_not_enabled()
    {
        $this->app['config']->set('activity-logger.enabled', false);

        $this->logger->track('My activity');

        static::assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_will_log_an_activity_when_enabled_option_is_null()
    {
        config(['activity-logger.enabled' => null]);

        $this->logger->track('My activity');

        static::assertEquals('My activity', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_to_the_default_log_by_default()
    {
        $this->logger->track('My activity');

        static::assertEquals(config('activity-logger.defaults.log-name'), $this->getLastActivity()->log_name);
    }

    /** @test */
    public function it_can_log_an_activity_to_a_specific_log()
    {
        $customLogName = 'secondLog';

        activity($customLogName)->track('My activity');
        static::assertEquals($customLogName, $this->getLastActivity()->log_name);

        $this->logger->useLog($customLogName)->track('My activity');
        static::assertEquals($customLogName, $this->getLastActivity()->log_name);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_subject()
    {
        $subject = Article::query()->first();

        $this->logger->performedOn($subject)->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertEquals($subject->id, $firstActivity->subject->id);
        static::assertInstanceOf(Article::class, $firstActivity->subject);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_causer()
    {
        $user = User::query()->first();

        $this->logger
            ->causedBy($user)
            ->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertEquals($user->id, $firstActivity->causer->id);
        static::assertInstanceOf(User::class, $firstActivity->causer);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_causer_when_there_is_no_web_guard()
    {
        config(['auth.guards.web' => null]);
        config(['auth.guards.foo' => ['driver' => 'session', 'provider' => 'users']]);
        config(['activity-logger.defaults.auth-driver' => 'foo']);

        $user = User::query()->first();

        $this->logger
            ->causedBy($user)
            ->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertEquals($user->id, $firstActivity->causer->id);
        static::assertInstanceOf(User::class, $firstActivity->causer);
    }

    /** @test */
    public function it_can_log_activity_with_properties()
    {
        $properties = [
            'property' => [
                'subProperty' => 'value',
            ],
        ];

        $this->logger
            ->withProperties($properties)
            ->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertInstanceOf(Collection::class, $firstActivity->properties);
        static::assertEquals('value', $firstActivity->getExtraProperty('property.subProperty'));
    }

    /** @test */
    public function it_can_log_activity_with_a_single_properties()
    {
        $this->logger
            ->withProperty('key', 'value')
            ->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertInstanceOf(Collection::class, $firstActivity->properties);
        static::assertEquals('value', $firstActivity->getExtraProperty('key'));
    }

    /** @test */
    public function it_can_translate_a_given_causer_id_to_an_object()
    {
        $userId = User::query()->first()->id;

        $this->logger
            ->causedBy($userId)
            ->track('My activity');

        $firstActivity = Activity::query()->first();

        static::assertInstanceOf(User::class, $firstActivity->causer);
        static::assertEquals($userId, $firstActivity->causer->id);
    }

    /**
     * @test
     *
     * @expectedException         \Arcanedev\ActivityLogger\Exceptions\CouldNotLogActivity
     * @expectedExceptionMessage  Could not determine a user with identifier `999`.
     */
    public function it_will_throw_an_exception_if_it_cannot_translate_a_causer_id()
    {
        $this->logger->causedBy(999);
    }

    /** @test */
    public function it_will_use_the_logged_in_user_as_the_causer_by_default()
    {
        /** @var  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user */
        $user = User::query()->find($userId = 1);

        $this->be($user);

        \activity()->track('hello poetsvrouwman');

        static::assertInstanceOf(User::class, $this->getLastActivity()->causer);
        static::assertEquals($userId, $this->getLastActivity()->causer->id);
    }

    /** @test */
    public function it_can_replace_the_placeholders()
    {
        $this->logger
            ->performedOn(Article::query()->create(['name' => 'article name']))
            ->causedBy(Article::query()->create(['name' => 'user name']))
            ->withProperties(['key' => 'value', 'key2' => ['subkey' => 'subvalue']])
            ->track('Subject name is :subject.name, causer name is :causer.name and property key is :properties.key and sub key :properties.key2.subkey');

        static::assertEquals(
            'Subject name is article name, causer name is user name and property key is value and sub key subvalue',
            $this->getLastActivity()->description
        );
    }

    /** @test */
    public function it_will_not_replace_non_placeholders()
    {
        $this->logger->track($description = 'hello: :hello');

        static::assertEquals($description, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_returns_an_instance_of_the_activity_after_logging()
    {
        $activityModel = $this->logger->track('test');

        static::assertInstanceOf(Activity::class, $activityModel);
    }

    /** @test */
    public function it_returns_an_instance_of_the_activity_log_after_logging_when_using_a_custom_model()
    {
        $className = get_class(new class extends Activity {});

        $this->app['config']->set('activity-logger.activities.model', $className);

        $activityModel = $this->logger->track('test');

        static::assertInstanceOf($className, $activityModel);
    }

    /** @test */
    public function it_will_not_log_an_activity_when_the_log_is_manually_disabled()
    {
        $this->logger->disable();
        $this->logger->track('My activity');

        static::assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_will_log_an_activity_when_the_log_is_manually_enabled()
    {
        config(['activity-logger.enabled' => false]);

        $this->logger->enable();
        $this->logger->track('My activity');

        static::assertEquals('My activity', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_accepts_null_parameter_for_caused_by()
    {
        $activity = $this->logger->causedBy(null)->track('nothing');

        static::assertSame('nothing', $activity->description);
        static::assertNull($activity->causer);
    }
}
