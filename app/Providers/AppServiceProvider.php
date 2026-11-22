<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\JobPost;
use App\Models\Service;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'job_post' => JobPost::class,
            'service' => Service::class,
            'App\Models\JobPost' => JobPost::class,
            'App\Models\Service' => Service::class,
            'App\\\\Models\\\\JobPost' => JobPost::class,
            'App\\\\Models\\\\Service' => Service::class,
        ]);
    }
}
