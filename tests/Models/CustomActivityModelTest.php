<?php namespace Arcanedev\ActivityLogger\Tests\Models;

use Arcanedev\ActivityLogger\Exceptions\InvalidConfiguration;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\CustomActivityModel;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\InvalidActivityModel;
use Arcanedev\ActivityLogger\Tests\TestCase;

/**
 * Class     CustomActivityModelTest
 *
 * @package  Arcanedev\ActivityLogger\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class CustomActivityModelTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var string */
    protected $activityDescription;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp(): void
    {
        $this->activityDescription = 'My activity';

        parent::setUp();

        collect(range(1, 5))->each(function (int $index) {
            $logName = "log{$index}";
            activity($logName)->info('hello everybody');
        });
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_log_activity_using_a_custom_model()
    {
        $this->app['config']->set('activity-logger.activities.model', CustomActivityModel::class);

        $activity = activity()->track($this->activityDescription);

        static::assertEquals($this->activityDescription, $activity->description);

        static::assertInstanceOf(CustomActivityModel::class, $activity);
    }

    /** @test */
    public function it_does_not_throw_an_exception_when_model_config_is_null()
    {
        $this->app['config']->set('activity-logger.activities.model', null);

        activity()->track($this->activityDescription);

        static::markTestAsPassed();
    }

    /** @test */
    public function it_throws_an_exception_when_model_doesnt_extend_package_model()
    {
        $this->expectException(InvalidConfiguration::class);

        $this->app['config']->set('activity-logger.activities.model', InvalidActivityModel::class);

        activity()->track($this->activityDescription);
    }

    /** @test */
    public function it_does_not_conflict_with_laravel_change_tracking()
    {
        $this->app['config']->set('activity-logger.activities.model', CustomActivityModel::class);

        $properties = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        /** @var CustomActivityModel $activity */
        $activity = activity()->withProperties($properties)->info($this->activityDescription);

        static::assertEquals($properties, $activity->changes()->toArray());
        static::assertEquals($properties, $activity->custom_properties->toArray());
    }
}
