<?php namespace Arcanedev\ActivityLogger\Tests\Traits;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Arcanedev\ActivityLogger\Tests\TestCase;
use Arcanedev\ActivityLogger\Traits\HasActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class     HasActivityTest
 *
 * @package  Arcanedev\ActivityLogger\Tests\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class HasActivityTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var  \Arcanedev\ActivityLogger\Tests\Stubs\Models\User */
    protected $user;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp(): void
    {
        parent::setUp();

        $this->user = new class() extends User {
            use HasActivity;
            use SoftDeletes;
        };

        static::assertSame(0, Activity::query()->count());
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_log_activity_on_subject_by_same_causer()
    {
        $user = $this->loginWithFakeUser();

        $user->name = 'HasActivity Name';
        $user->save();

        static::assertSame(1, Activity::query()->count());

        static::assertInstanceOf(get_class($this->user), $this->getLastActivity()->subject);
        static::assertEquals($user->id, $this->getLastActivity()->subject->id);
        static::assertEquals($user->id, $this->getLastActivity()->causer->id);
        static::assertCount(1, $user->activities);
        static::assertCount(1, $user->actions);
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Login with a fake user.
     *
     * @return \Arcanedev\ActivityLogger\Tests\Stubs\Models\User
     */
    protected function loginWithFakeUser()
    {
        $user = new $this->user();
        $user = $user::find(1);

        $this->be($user);

        return $user;
    }
}
