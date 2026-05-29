<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
   public function boot(): void
   {
    Route::middleware(['api', 'auth:sanctum', 'admin'])
        ->prefix('admin')
        ->group(base_path('routes/admin.php'));
   }
}