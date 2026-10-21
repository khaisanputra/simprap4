<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        // Blade directive untuk membuat link sorting yang aman
        // pada tabel Menu User (mempertahankan filter & pencarian aktif).
        Blade::directive('sortlink', function ($expression) {
            return "<?php echo \App\Support\SortHelper::sortUrl($expression); ?>";
        });
    }
}
