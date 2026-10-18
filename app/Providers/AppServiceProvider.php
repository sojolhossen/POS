<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Blade::directive('bdt', function ($expression) {
            return "<?php echo \App\Helpers\FormattingHelper::currency($expression); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('posDate', function ($expression) {
            return "<?php echo \App\Helpers\FormattingHelper::date($expression); ?>";
        });

        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $balanceService = app(\App\Services\StoreBalanceService::class);
                $view->with('headerBalanceData', $balanceService->getHeaderBalanceData(auth()->user()));
            }
        });
    }
}
