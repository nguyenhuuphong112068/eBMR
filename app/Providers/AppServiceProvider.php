<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Services\PendingApprovalCounter;

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
        Paginator::useBootstrap();

        View::composer('layout.leftNAV', function ($view) {
            $userId = session('user')['userId'] ?? null;
            $view->with('pendingApprovalCount', PendingApprovalCounter::countForUser($userId));
        });
    }
}
