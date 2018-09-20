<?php namespace Arcanedev\ActivityLogger;

use Arcanedev\Support\PackageServiceProvider;

/**
 * Class     ActivityLoggerServiceProvider
 *
 * @package  Arcanedev\ActivityLogger
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class ActivityLoggerServiceProvider extends PackageServiceProvider
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /**
     * Package name.
     *
     * @var string
     */
    protected $package = 'activity-logger';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    /**
     * Register the service provider.
     */
    public function register()
    {
        parent::register();

        $this->registerConfig();
        $this->registerConsoleServiceProvider(Providers\CommandsServiceProvider::class);
        $this->bind(Contracts\ActivityLogger::class, ActivityLogger::class);
    }

    /**
     * Boot the service provider.
     */
    public function boot()
    {
        parent::boot();

        $this->publishConfig();
        $this->loadMigrations();
        $this->publishTranslations();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Contracts\ActivityLogger::class
        ];
    }
}
