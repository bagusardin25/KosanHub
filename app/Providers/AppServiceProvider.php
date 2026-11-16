<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
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
        // Semua tanggal (now(), cast 'date') memakai CarbonImmutable supaya perhitungan
        // tagihan tidak mengubah objek tanggal aslinya secara tidak sengaja.
        Date::use(CarbonImmutable::class);
    }
}
