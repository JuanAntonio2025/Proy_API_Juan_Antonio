<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Petition;
use App\Policies\PetitionPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Petition::class => PetitionPolicy::class,
    ];

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
        Gate::policy(Petition::class, PetitionPolicy::class);
    }
}
