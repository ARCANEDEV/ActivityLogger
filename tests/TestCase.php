<?php namespace Arcanedev\ActivityLogger\Tests;

use Arcanedev\ActivityLogger\Models\Activity;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\Article;
use Arcanedev\ActivityLogger\Tests\Stubs\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Class     TestCase
 *
 * @package  Arcanedev\ActivityLogger\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Database\ConsoleServiceProvider::class,
            \Arcanedev\ActivityLogger\ActivityLoggerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        /** @var  \Illuminate\Contracts\Config\Repository  $config */
        $config = $app['config'];

        $config->set('auth.providers.users.model', User::class);
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    protected function checkRequirements()
    {
        parent::checkRequirements();

        collect($this->getAnnotations())->filter(function ($location) {
            return in_array('!Travis', array_get($location, 'requires', []));
        })->each(function ($location) {
            getenv('TRAVIS') && $this->markTestSkipped('Travis will not run this test.');
        });
    }

    protected function setUpDatabase()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->createTables(['articles', 'users']);
        $this->seedModels([Article::class, User::class]);
    }

    protected function createTables($tableNames)
    {
        collect($tableNames)->each(function (string $tableName) {
            $this->app['db']->connection()->getSchemaBuilder()->create($tableName, function (Blueprint $table) use ($tableName) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('text')->nullable();
                $table->timestamps();
                $table->softDeletes();
                if ($tableName === 'articles') {
                    $table->integer('user_id')->unsigned()->nullable();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('json')->nullable();
                }
            });
        });
    }

    protected function seedModels($modelClasses)
    {
        collect($modelClasses)->each(function (string $modelClass) {
            foreach (range(1, 0) as $index) {
                $modelClass::create(['name' => "name {$index}"]);
            }
        });
    }

    /**
     * Get the last activity.
     *
     * @return \Arcanedev\ActivityLogger\Models\Activity|mixed
     */
    protected function getLastActivity()
    {
        return Activity::query()->get()->last();
    }

    protected static function markTestAsPassed()
    {
        static::assertTrue(true);
    }
}
