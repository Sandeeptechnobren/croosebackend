<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AppointmentController;
// Route::middleware('auth:sanctum')->get('/clients', function (Request $request) {
//     return $request->clients();
// });

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    
    // ✅ Protected Routes (Only for authenticated users)
    Route::middleware('auth:sanctum')->group(function () {
        
         Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
   
});
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-reset-password', [AuthController::class, 'verifyOtpAndResetPassword']);


//services API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/services', [ServicesController::class, 'index']);
    Route::post('/services', [ServicesController::class, 'store']);
    Route::get('/services/{id}', [ServicesController::class, 'show']);
    Route::put('/services/{id}', [ServicesController::class, 'update']);
    Route::delete('/services/{id}', [ServicesController::class, 'destroy']);
});

//products API

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/products', [ProductsController::class, 'index']);
    Route::post('/products', [ProductsController::class, 'store']);
    Route::get('/products/{id}', [ProductsController::class, 'show']);
    Route::put('/products/{id}', [ProductsController::class, 'update']);
    Route::delete('/products/{id}', [ProductsController::class, 'destroy']);


});
//customer API
Route::post('/customer/register', [CustomerController::class, 'store']);
// Route::middleware('auth:sanctum')->get('/client/customers', [ClientCustomerController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'show']);

    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
});

Route::post('/appointments', [AppointmentController::class, 'store']);



