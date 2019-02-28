<?php namespace Arcanedev\ActivityLogger\Tests\Traits;

use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Arcanedev\ActivityLogger\Tests\TestCase;

/**
 * Class     CausesActivityTest
 *
 * @package  Arcanedev\ActivityLogger\Tests\Traits
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class CausesActivityTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_get_all_activities_for_the_causer()
    {
        /** @var  \Arcanedev\ActivityLogger\Tests\Stubs\Models\User  $causer */
        $causer = User::query()->first();

        activity()->by($causer)->track('perform activity');
        activity()->by($causer)->track('perform another activity');

        static::assertCount(2, $causer->activities);
    }
}
