<?php namespace Arcanedev\ActivityLogger\Tests\Concerns;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\TestCase;

/**
 * Class     LoggerTest
 *
 * @package  Arcanedev\ActivityLogger\Tests\Concerns
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class LoggerTest extends TestCase
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
    public function it_can_log_default_type()
    {
        $this->logger->track('My activity');

        $this->assertLogInDB($this->getLastActivity());
    }

    /** @test */
    public function it_can_log_emergency_type()
    {
        $this->logger->emergency('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'emergency');
    }

    /** @test */
    public function it_can_log_alert_type()
    {
        $this->logger->alert('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'alert');
    }

    /** @test */
    public function it_can_log_critical_type()
    {
        $this->logger->critical('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'critical');
    }

    /** @test */
    public function it_can_log_error_type()
    {
        $this->logger->error('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'error');
    }

    /** @test */
    public function it_can_log_warning_type()
    {
        $this->logger->warning('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'warning');
    }

    /** @test */
    public function it_can_log_notice_type()
    {
        $this->logger->notice('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'notice');
    }

    /** @test */
    public function it_can_log_info_type()
    {
        $this->logger->info('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'info');
    }

    /** @test */
    public function it_can_log_debug_type()
    {
        $this->logger->debug('My activity');

        $this->assertLogInDB($this->getLastActivity(), 'debug');
    }

    /* -----------------------------------------------------------------
     |  Other function
     | -----------------------------------------------------------------
     */

    protected function assertLogInDB(Activity $activity, $level = 'info')
    {
        $this->assertDatabaseHas($activity->getTable(), [
            'id'          => $activity->id,
            'log_name'    => 'default',
            'log_level'   => $level,
            'properties'  => "[]",
            'description' => 'My activity',
        ]);
    }
}
