<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;

Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {

Route::get('/dashboard', [AdminController::class, 'dashboard']);

Route::get('/users', [AdminController::class, 'users']);
Route::post('/user/status/{id}', [AdminController::class, 'toggleUserStatus']);

Route::get('/orders', [AdminController::class, 'orders']);
Route::post('/orders/status', [AdminController::class, 'updateOrderStatus']);

Route::get('/products', [AdminController::class, 'products']);
Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);

Route::get('/services', [AdminController::class, 'services']);
Route::delete('/services/{id}', [AdminController::class, 'deleteService']);

Route::get('/subscriptions', [AdminController::class, 'subscriptions']);
Route::post('/subscription/archive/{id}', [AdminController::class, 'archiveSubscription']);

});